<?php

declare(strict_types=1);

namespace App\Service\Content;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;

/**
 * MarkdownService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MarkdownService implements MarkdownServiceInterface
{
    private const bool LATEX = true;
    private CommonMarkConverter $converter;

    /**
     * MarkdownService constructor.
     */
    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'escape',  // Secure HTML input
            'allow_unsafe_links' => false
        ]);
    }

    /**
     * To convert to HTML.
     *
     * @throws CommonMarkException
     */
    public function convertToHtml(?string $content = null): ?string
    {
        $contentType = $content ? $this->detectContentType($content) : null;
        if ('html' === $contentType) {
            $content = $this->htmlConvert($content);
        } elseif ($content && 'markdown' === $contentType) {

            $latexBlocks = [];
            $mediaBlocks = [];

            // 1. Preserve LaTeX expressions by replacing them with unique placeholders
            $content = preg_replace_callback('/(\$\$.*?\$\$|\$.*?\$)/s', function ($matches) use (&$latexBlocks) {
                $key = 'LATEX_' . count($latexBlocks) . '_TOKEN';
                $latexBlocks[$key] = $matches[0]; // Store the original LaTeX block
                return $key;
            }, $content);

            // 2. Preserve <video> and <img> tags using unique tokens
            $content = preg_replace_callback('/(<video.*?<\/video>)|(<img[^>]+>)/is', function ($matches) use (&$mediaBlocks) {
                $key = 'MEDIA_' . count($mediaBlocks) . '_TOKEN';
                $mediaBlocks[$key] = $matches[0];
                return $key;
            }, $content);

            // 3. Convert Markdown to HTML
            $convertedContent = $this->converter->convert($content);
            $convertedContent = $convertedContent->getContent();

            // 4. Restore LaTeX expressions and media tags
            $convertedContent = str_replace(array_keys($latexBlocks), array_values($latexBlocks), $convertedContent);
            $convertedContent = str_replace(array_keys($mediaBlocks), array_values($mediaBlocks), $convertedContent);

            $content = $this->wrapLatexBlocks($convertedContent);
        }

        return self::LATEX ? $this->latexConvert($content) : $content;
    }

    /**
     * To convert and clean HTML content.
     */
    public function htmlConvert(string $html): ?string
    {
        // Suppression de balises inutiles <el> </el>.
        $removeTags = ['head', 'script', 'style', 'title'];
        foreach ($removeTags as $tag) {
            $html = preg_replace('/<'.$tag.'[^>]*?>.*?<\/'.$tag.'>/is', '', $html);
        }

        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);

        // Supprimer les balises XML
        $html = preg_replace('/<\?xml.*?\?>/is', '', $html);
        $html = preg_replace('/<xml[^>]*?>.*?<\/xml>/is', '', $html);

        // Supprimer les commentaires HTML
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        $html = $this->removeHTMLTag($html, 'html', true);
        $html = $this->removeHTMLTag($html, 'body', true);
        $html = $this->removeHTMLTag($html, 'DOCTYPE', true);
        $html = $this->removeElementsByClass($html, 'jp-InputArea-prompt');
        $html = $this->removeElementsByClass($html, 'jp-OutputArea-prompt');
        $html = $this->removeElementsByClass($html, 'anchor-link');
        $html = trim($html);
        $html = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $html);
        $html = preg_replace('/(<[^>]+) id=".*?"/i', '$1', $html);
        $html = preg_replace('/(<[^>]+) class=".*?"/i', '$1', $html);
        $html = $this->addAltToImagesWithoutDOM($html);

        // Expression régulière pour supprimer les attributs qui commencent par 'data-'
        $pattern = '/\sdata-[\w-]+="[^"]*"/i';
        // Remplacer les attributs 'data-' par une chaîne vide
        $html = preg_replace($pattern, '', $html);

        // Utiliser preg_replace_callback pour traiter uniquement les balises <pre>
        $html = preg_replace_callback('/<pre.*?>(.*?)<\/pre>/is', function ($matches) {
            // Appliquer strip_tags uniquement à l'intérieur de la balise <pre>
            $cleanedContent = strip_tags($matches[1]);
            return '<pre><code>' . $cleanedContent . '</code></pre>';
        }, $html);

        $html = $this->wrapLatexBlocks($html);

        // Supprimer les balises vides
        return preg_replace('/<(\w+)[^>]*>\s*(&nbsp;|\s)*<\/\1>/is', '', $html);
    }

    /**
     * To wrap $$ LaTex blocks.
     */
    private function wrapLatexBlocks($html): ?string
    {
        return preg_replace('/\$\$(.*?)\$\$/s', '<span class="text-center d-inline-block w-100">$$\1$$</span>', $html);
    }

    /**
     * To add alt attribute to images.
     */
    private function addAltToImagesWithoutDOM($html, $defaultAlt = "Image"): array|string|null
    {
        $pattern = '/<img((?!alt=)[^>]+)>/i';
        return $html ? preg_replace_callback($pattern, function ($matches) use ($defaultAlt) {
            return '<img'.$matches[1].' alt="'. htmlspecialchars($defaultAlt, ENT_QUOTES).'">';
        }, $html) : $html;
    }

    /**
     * To convert LaTeX content.
     */
    private function latexConvert(string $content): ?string
    {
        $parseDown = new \Parsedown();

        // 1. Temporarily replace LaTeX formulas with unique placeholders without underscores or special characters.
        $placeholders = [];
        $content = preg_replace_callback('/(\$\$.*?\$\$|\$.*?\$|\\\(.*?\\\))/s', function ($matches) use (&$placeholders) {
            // Use a unique and secure identifier for each LaTeX
            $key = 'LATEX_' . uniqid('latex_', true); // Fully secure identifier.
            $placeholders[$key] = htmlspecialchars($matches[0], ENT_NOQUOTES, 'UTF-8');
            return $key;
        }, $content);

        // 2. Convert Markdown text to HTML.
        $content = $parseDown->text($content);

        // 3. Re-inject LaTeX formulas after processing Markdown
        foreach ($placeholders as $key => $formula) {
            $content = str_replace($key, '<span class="latex-formula">'.$formula.'</span>', $content);
        }

        // 4. Replace LaTeX formulas that start and end with a single $ with $$...$$, but not inside <code> and <pre> tags
        $content = preg_replace_callback('/\$(.*?)\$/s', function ($matches) {
            // On ne remplace pas si la chaîne est dans <code> ou <pre>
            if (!str_contains($matches[0], '<code>') && !str_contains($matches[0], '<pre>')) {
                return '$$' . $matches[1] . '$$';
            }
            return $matches[0]; // Si dans <code> ou <pre>, on laisse tel quel
        }, $content);

        // 5. Convert malformed formulas
        $content = str_replace('$$$$', '$$', $content);

        // 6. Convert Markdown text to HTML
        return $parseDown->text($content);
    }

    /**
     * To extract H1 in content.
     */
    public function extractMarkdownH1(?string $content = null): ?string
    {
        if ($content && preg_match('/<h1>(.*?)<\/h1>/is', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * To detect content type.
     */
    public function detectContentType(string $content): string
    {
        $content = str_replace(["\r\n", "\r", "\n"], '', $content);
        $content = preg_replace('/<video.*?>.*?<\/video>/i', '', $content);  // Supprimer les vidéos
        $content = preg_replace('/<img.*?\/>/i', '', $content);  // Supprimer les images

        // Vérifier si le contenu contient des balises HTML
        if (preg_match('/<\/?[a-z][\s\S]*>/i', $content)) {
            // Si le contenu contient des balises HTML, on le considère comme HTML
            return 'html';
        }

        // Vérifier les éléments typiques du Markdown
        // Titres Markdown : "# " ou "## "
        if (preg_match('/^(#|##|###|####|#####|######)\s/m', $content)) {
            return 'markdown';
        }

        // Vérifier les listes Markdown : * ou -
        if (preg_match('/^\* /m', $content) || preg_match('/^- /m', $content)) {
            return 'markdown';
        }

        // Vérifier les formules mathématiques en Markdown : $...$ et $$...$$
        if (preg_match('/\$(.*?)\$/s', $content) || preg_match('/\$\$(.*?)\$\$/s', $content)) {
            return 'markdown';
        }

        // Vérifier les blocs de code : ```
        if (preg_match('/```/s', $content)) {
            return 'markdown';
        }

        return 'unknown'; // Si aucun format n'est clairement détecté
    }

    /**
     * To remove elements by class.
     */
    private function removeElementsByClass(string $content, string $class): string
    {
        // Expression régulière pour trouver les éléments avec la classe spécifique
        $pattern = '/<[^>]*class="[^"]*' . preg_quote($class, '/') . '[^"]*"[^>]*>.*?<\/[^>]+>/is';

        // Remplacer ces éléments par une chaîne vide
        return preg_replace($pattern, '', $content);
    }

    /**
     * To remove HTML tag.
     */
    private function removeHTMLTag(string $content, string $tag, bool $keepContent): string
    {
        // Expression régulière pour trouver la balise html (ou autre tag) et garder son contenu
        $pattern = '/<'.$tag.'[^>]*>(.*?)<\/'.$tag.'>/is';

        // Remplacer la balise par son contenu
        return $keepContent ? preg_replace($pattern, '$1', $content)
            : preg_replace($pattern, '', $content);
    }
}
