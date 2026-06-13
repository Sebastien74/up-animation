<?php

declare(strict_types=1);

namespace App\Service\Admin;

/**
 * PageAnalyzer.
 *
 * Static analysis of a rendered front HTML page, focused on rendering performance
 * (render-blocking resources, CLS risks, page weight, DOM size, lazy-loading).
 * This is NOT a measured Lighthouse audit: the score is an indicative heuristic.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PageAnalyzer implements PageAnalyzerInterface
{
    public function analyze(string $html, ?string $urlCode = null): array
    {
        $bytes = strlen($html);
        $meta = [
            'urlCode' => $urlCode,
            'bytes' => $bytes,
            'kb' => (int) round($bytes / 1024),
        ];

        if ('' === trim($html)) {
            return [
                'meta' => $meta,
                'score' => null,
                'findings' => [[
                    'id' => 'empty',
                    'severity' => 'high',
                    'label' => 'Page vide',
                    'value' => 'Aucun HTML rendu',
                    'reco' => "La page n'a renvoyé aucun contenu. Vérifiez qu'elle est accessible et publiée.",
                ]],
            ];
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);

        $domCount = $this->count($xpath, '//body//*');

        // Images.
        $images = $xpath->query('//img');
        $imgTotal = $images->length;
        $imgNoDim = 0;
        $imgNoLazy = 0;
        $imgLegacy = 0;
        foreach ($images as $img) {
            /** @var \DOMElement $img */
            if ('' === $img->getAttribute('width') || '' === $img->getAttribute('height')) {
                ++$imgNoDim;
            }
            if ('lazy' !== strtolower($img->getAttribute('loading'))) {
                ++$imgNoLazy;
            }
            $src = $img->getAttribute('src');
            if (preg_match('/\.(jpe?g|png|gif)(\?.*)?$/i', $src)) {
                ++$imgLegacy;
            }
        }

        // Scripts.
        $blockingScripts = 0;
        foreach ($xpath->query('//head//script[@src]') as $script) {
            /** @var \DOMElement $script */
            if (!$script->hasAttribute('async') && !$script->hasAttribute('defer')) {
                ++$blockingScripts;
            }
        }
        $totalScripts = $this->count($xpath, '//script[@src]');

        // Render-blocking stylesheets in head (not media="print").
        $blockingCss = 0;
        foreach ($xpath->query('//head//link[@rel="stylesheet"]') as $link) {
            /** @var \DOMElement $link */
            if ('print' !== strtolower($link->getAttribute('media'))) {
                ++$blockingCss;
            }
        }

        $inlineStyles = $this->count($xpath, '//*[@style]');
        $h1 = $this->count($xpath, '//h1');
        $preconnect = $this->count($xpath, '//head//link[@rel="preconnect"]');
        $preload = $this->count($xpath, '//head//link[@rel="preload"]');

        $iframeNoLazy = 0;
        foreach ($xpath->query('//iframe') as $iframe) {
            /** @var \DOMElement $iframe */
            if ('lazy' !== strtolower($iframe->getAttribute('loading'))) {
                ++$iframeNoLazy;
            }
        }

        $findings = [];

        // Page weight.
        $findings[] = [
            'id' => 'weight',
            'severity' => $bytes > 300 * 1024 ? 'high' : ($bytes > 150 * 1024 ? 'medium' : 'ok'),
            'label' => 'Poids du HTML',
            'value' => $meta['kb'].' Ko',
            'reco' => $bytes > 150 * 1024 ? 'HTML lourd : réduisez le contenu inline (SVG, styles, scripts) et la profondeur du DOM.' : '',
        ];

        // DOM size.
        $findings[] = [
            'id' => 'dom',
            'severity' => $domCount > 1500 ? 'high' : ($domCount > 800 ? 'medium' : 'ok'),
            'label' => 'Taille du DOM',
            'value' => $domCount.' éléments',
            'reco' => $domCount > 800 ? 'DOM volumineux : simplifiez la structure des zones/colonnes/blocs pour accélérer le rendu.' : '',
        ];

        // Images without dimensions (CLS).
        $findings[] = [
            'id' => 'img-dimensions',
            'severity' => $imgNoDim > 0 ? 'high' : 'ok',
            'label' => 'Images sans dimensions (width/height)',
            'value' => $imgNoDim.' / '.$imgTotal,
            'reco' => $imgNoDim > 0 ? 'Ajoutez width/height (ou aspect-ratio) pour éviter les décalages de mise en page (CLS).' : '',
        ];

        // Images without lazy-loading.
        $findings[] = [
            'id' => 'img-lazy',
            'severity' => $imgNoLazy > 3 ? 'medium' : ($imgNoLazy > 0 ? 'low' : 'ok'),
            'label' => 'Images sans lazy-load',
            'value' => $imgNoLazy.' / '.$imgTotal,
            'reco' => $imgNoLazy > 0 ? 'Activez loading="lazy" sur les images hors écran (hors visuel principal de la 1ʳᵉ zone).' : '',
        ];

        // Legacy image formats.
        $findings[] = [
            'id' => 'img-format',
            'severity' => $imgLegacy > 0 ? 'medium' : 'ok',
            'label' => 'Images aux formats anciens (jpg/png/gif)',
            'value' => $imgLegacy.' / '.$imgTotal,
            'reco' => $imgLegacy > 0 ? 'Servez des formats modernes (WebP/AVIF) pour réduire le poids des images.' : '',
        ];

        // Render-blocking scripts in head.
        $findings[] = [
            'id' => 'scripts-blocking',
            'severity' => $blockingScripts > 0 ? 'high' : 'ok',
            'label' => 'Scripts bloquants dans le <head>',
            'value' => $blockingScripts.' / '.$totalScripts,
            'reco' => $blockingScripts > 0 ? 'Ajoutez defer/async ou déplacez ces scripts en fin de <body> pour ne pas bloquer le rendu (TBT).' : '',
        ];

        // Render-blocking CSS.
        $findings[] = [
            'id' => 'css-blocking',
            'severity' => $blockingCss > 2 ? 'medium' : ($blockingCss > 0 ? 'low' : 'ok'),
            'label' => 'Feuilles de style bloquantes',
            'value' => (string) $blockingCss,
            'reco' => $blockingCss > 1 ? 'Limitez le CSS bloquant : inlinez le CSS critique et chargez le reste en asynchrone (preload/onload).' : '',
        ];

        // Inline styles.
        $findings[] = [
            'id' => 'inline-styles',
            'severity' => $inlineStyles > 30 ? 'low' : 'ok',
            'label' => 'Styles inline (attribut style)',
            'value' => (string) $inlineStyles,
            'reco' => $inlineStyles > 30 ? 'Nombreux styles inline : préférez des classes pour un meilleur cache et un HTML plus léger.' : '',
        ];

        // Single H1.
        $findings[] = [
            'id' => 'h1',
            'severity' => 1 === $h1 ? 'ok' : ($h1 > 1 ? 'medium' : 'high'),
            'label' => 'Titre principal (H1)',
            'value' => $h1.' H1',
            'reco' => 1 === $h1 ? '' : (0 === $h1 ? 'Aucun H1 : ajoutez un titre principal unique.' : 'Plusieurs H1 : conservez un seul H1 par page.'),
        ];

        // Iframes without lazy.
        $findings[] = [
            'id' => 'iframe-lazy',
            'severity' => $iframeNoLazy > 0 ? 'medium' : 'ok',
            'label' => 'Iframes sans lazy-load',
            'value' => (string) $iframeNoLazy,
            'reco' => $iframeNoLazy > 0 ? 'Ajoutez loading="lazy" sur les iframes (vidéos, cartes) pour différer leur chargement.' : '',
        ];

        // Resource hints (informational).
        $findings[] = [
            'id' => 'hints',
            'severity' => 'info',
            'label' => 'Indices de ressources (preconnect/preload)',
            'value' => $preconnect.' preconnect · '.$preload.' preload',
            'reco' => 0 === $preload ? 'Pensez à précharger les polices et le visuel principal (hero) pour accélérer le LCP.' : '',
        ];

        return [
            'meta' => $meta,
            'score' => $this->score($findings),
            'findings' => $this->sort($findings),
        ];
    }

    private function count(\DOMXPath $xpath, string $query): int
    {
        $nodes = $xpath->query($query);

        return false === $nodes ? 0 : $nodes->length;
    }

    /**
     * Indicative heuristic score (0-100), NOT a measured Lighthouse score.
     *
     * @param array<int, array<string, mixed>> $findings
     */
    private function score(array $findings): int
    {
        $penalty = ['high' => 18, 'medium' => 9, 'low' => 3, 'ok' => 0, 'info' => 0];
        $score = 100;
        foreach ($findings as $finding) {
            $score -= $penalty[$finding['severity']] ?? 0;
        }

        return max(0, min(100, $score));
    }

    /**
     * Sort findings by severity (most critical first).
     *
     * @param array<int, array<string, mixed>> $findings
     *
     * @return array<int, array<string, mixed>>
     */
    private function sort(array $findings): array
    {
        $order = ['high' => 0, 'medium' => 1, 'low' => 2, 'info' => 3, 'ok' => 4];
        usort($findings, static fn (array $a, array $b): int => ($order[$a['severity']] ?? 9) <=> ($order[$b['severity']] ?? 9));

        return $findings;
    }
}
