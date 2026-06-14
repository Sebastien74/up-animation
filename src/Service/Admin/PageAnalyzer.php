<?php

declare(strict_types=1);

namespace App\Service\Admin;

/**
 * PageAnalyzer.
 *
 * Static analysis of a rendered front HTML page (preview mode, logged-in admin only).
 * It only parses the provided HTML string: no network call, no side effect, and no
 * impact on the public front navigation. The score is an indicative heuristic, NOT a
 * measured Lighthouse / Core Web Vitals audit.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PageAnalyzer implements PageAnalyzerInterface
{
    public function analyze(string $html, ?string $urlCode = null, ?string $ownHost = null): array
    {
        $bytes = strlen($html);
        $meta = [
            'urlCode' => $urlCode,
            'bytes' => $bytes,
            'kb' => (int) round($bytes / 1024),
            'dom' => 0,
            'images' => 0,
            'scripts' => 0,
            'requests' => 0,
        ];

        if ('' === trim($html)) {
            return [
                'meta' => $meta,
                'score' => null,
                'summary' => ['high' => 1, 'medium' => 0, 'low' => 0],
                'groups' => [[
                    'id' => 'error',
                    'label' => 'Erreur',
                    'counts' => ['high' => 1, 'medium' => 0, 'low' => 0],
                    'findings' => [$this->f('empty', 'high', 'Page vide', 'Aucun HTML rendu', "La page n'a renvoyé aucun contenu. Vérifiez qu'elle est accessible.")],
                ]],
            ];
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);

        [$maxDepth, $maxChildren] = $this->domShape($dom);
        $domCount = $this->count($xpath, '//body//*');
        $ownHost = $ownHost ? strtolower($ownHost) : $this->ownHostFromHtml($xpath);

        $meta['dom'] = $domCount;
        $meta['images'] = $this->count($xpath, '//img');
        $meta['scripts'] = $this->count($xpath, '//script[@src]');
        $meta['requests'] = $this->requestCount($xpath);
        $meta['externalDomains'] = count($this->externalHosts($xpath, $ownHost));

        $groups = [
            ['id' => 'perf', 'label' => 'Performance & rendu', 'findings' => $this->perf($xpath, $html, $bytes, $domCount, $maxDepth, $maxChildren)],
            ['id' => 'resources', 'label' => 'Ressources & chargement', 'findings' => $this->resources($xpath, $html, $ownHost)],
            ['id' => 'images', 'label' => 'Images & médias', 'findings' => $this->images($xpath, $html)],
            ['id' => 'structure', 'label' => 'Structure & DOM', 'findings' => $this->structure($xpath, $maxDepth, $maxChildren)],
            ['id' => 'seo', 'label' => 'SEO & métadonnées', 'findings' => $this->seo($xpath)],
            ['id' => 'a11y', 'label' => 'Accessibilité', 'findings' => $this->accessibility($xpath)],
            ['id' => 'best', 'label' => 'Bonnes pratiques & sécurité', 'findings' => $this->bestPractices($xpath, $html)],
        ];

        $all = [];
        foreach ($groups as &$group) {
            $group['findings'] = $this->sort($group['findings']);
            $group['counts'] = $this->groupCounts($group['findings']);
            $all = array_merge($all, $group['findings']);
        }
        unset($group);

        return [
            'meta' => $meta,
            'score' => $this->score($all),
            'summary' => $this->groupCounts($all),
            'groups' => $groups,
        ];
    }

    /**
     * Performance & rendering checks.
     *
     * @return array<int, array<string, mixed>>
     */
    private function perf(\DOMXPath $xpath, string $html, int $bytes, int $domCount, int $maxDepth, int $maxChildren): array
    {
        $blockingScripts = 0;
        foreach ($xpath->query('//head//script[@src]') as $script) {
            /** @var \DOMElement $script */
            if (!$script->hasAttribute('async') && !$script->hasAttribute('defer') && 'module' !== strtolower($script->getAttribute('type'))) {
                ++$blockingScripts;
            }
        }
        $totalScripts = $this->count($xpath, '//script[@src]');

        $blockingCss = 0;
        foreach ($xpath->query('//head//link[@rel="stylesheet"]') as $link) {
            /** @var \DOMElement $link */
            if ('print' !== strtolower($link->getAttribute('media'))) {
                ++$blockingCss;
            }
        }

        $inlineJsBytes = 0;
        foreach ($xpath->query('//script[not(@src)]') as $script) {
            $inlineJsBytes += strlen($script->textContent);
        }
        $inlineCssBytes = 0;
        foreach ($xpath->query('//style') as $style) {
            $inlineCssBytes += strlen($style->textContent);
        }

        $base64 = preg_match_all('/data:[^"\')\s]+;base64/i', $html);
        $requests = $this->requestCount($xpath);

        return [
            $this->f('weight', $this->sev($bytes, 150 * 1024, 300 * 1024), 'Poids du HTML', $this->kb($bytes),
                $bytes > 150 * 1024 ? 'HTML lourd : réduisez le contenu inline (SVG, styles, scripts) et la profondeur du DOM.' : ''),
            $this->f('requests', $this->sev($requests, 40, 80), 'Requêtes estimées', (string) $requests.' ressources',
                $requests > 40 ? 'Beaucoup de ressources : regroupez/différez scripts et styles, activez le cache et le lazy-load.' : ''),
            $this->f('scripts-blocking', $blockingScripts > 0 ? 'high' : 'ok', 'Scripts bloquants dans le <head>', $blockingScripts.' / '.$totalScripts,
                $blockingScripts > 0 ? 'Ajoutez defer/async ou déplacez ces scripts en fin de <body> (réduit le TBT).' : ''),
            $this->f('css-blocking', $this->sev($blockingCss, 1, 3), 'Feuilles de style bloquantes', (string) $blockingCss,
                $blockingCss > 1 ? 'Inlinez le CSS critique et chargez le reste en asynchrone (preload + onload).' : ''),
            $this->f('inline-js', $this->sev($inlineJsBytes, 30 * 1024, 80 * 1024), 'JavaScript inline', $this->kb($inlineJsBytes),
                $inlineJsBytes > 30 * 1024 ? 'Externalisez le JS inline volumineux pour profiter du cache navigateur.' : ''),
            $this->f('inline-css', $this->sev($inlineCssBytes, 30 * 1024, 80 * 1024), 'CSS inline (<style>)', $this->kb($inlineCssBytes),
                $inlineCssBytes > 30 * 1024 ? 'Limitez le CSS inline au critique ; externalisez le reste.' : ''),
            $this->f('base64', $this->sev($base64, 3, 10), 'Ressources en base64 (data:)', (string) $base64,
                $base64 > 3 ? 'Les data: en base64 gonflent le HTML et ne sont pas mises en cache : préférez des fichiers.' : ''),
            $this->f('dom', $this->sev($domCount, 800, 1500), 'Taille du DOM', $domCount.' éléments',
                $domCount > 800 ? 'DOM volumineux : simplifiez zones/colonnes/blocs pour accélérer le rendu.' : ''),
        ];
    }

    /**
     * Resources & loading hints.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resources(\DOMXPath $xpath, string $html, ?string $ownHost = null): array
    {
        $totalScripts = $this->count($xpath, '//script[@src]');
        $deferAsync = $this->count($xpath, '//script[@src][@defer or @async]');
        $stylesheets = $this->count($xpath, '//link[@rel="stylesheet"]');
        $preconnect = $this->count($xpath, '//head//link[@rel="preconnect"]');
        $dnsPrefetch = $this->count($xpath, '//head//link[@rel="dns-prefetch"]');
        $preload = $this->count($xpath, '//head//link[@rel="preload"]');
        $preloadFont = $this->count($xpath, '//head//link[@rel="preload"][@as="font"]');
        $external = $this->externalHosts($xpath, $ownHost);

        return [
            $this->f('ext-domains', $this->sev(count($external), 4, 10), 'Domaines tiers contactés', (string) count($external),
                count($external) > 4 ? 'Chaque domaine tiers ajoute une résolution DNS + handshake : limitez-les ou préconnectez-vous.' : ''),
            $this->f('scripts-total', 'info', 'Scripts externes', (string) $totalScripts, ''),
            $this->f('scripts-defer', 'info', 'Scripts différés (defer/async)', $deferAsync.' / '.$totalScripts,
                $totalScripts > 0 && $deferAsync < $totalScripts ? 'Privilégiez defer/async sur les scripts non critiques.' : ''),
            $this->f('stylesheets', 'info', 'Feuilles de style', (string) $stylesheets, ''),
            $this->f('preconnect', 'info', 'preconnect / dns-prefetch', $preconnect.' / '.$dnsPrefetch,
                0 === $preconnect && !empty($external) ? 'Ajoutez preconnect vers les domaines tiers critiques (polices, CDN).' : ''),
            $this->f('preload', 0 === $preload ? 'low' : 'ok', 'Ressources préchargées (preload)', (string) $preload,
                0 === $preload ? 'Préchargez le visuel principal (hero) et les polices pour améliorer le LCP.' : ''),
            $this->f('preload-font', 0 === $preloadFont ? 'low' : 'ok', 'Polices préchargées', (string) $preloadFont,
                0 === $preloadFont ? 'Préchargez les polices web (preload as="font" crossorigin) pour éviter le FOIT/FOUT.' : ''),
        ];
    }

    /**
     * Images & media.
     *
     * @return array<int, array<string, mixed>>
     */
    private function images(\DOMXPath $xpath, string $html): array
    {
        $images = $xpath->query('//img');
        $total = $images->length;
        $noDim = $noLazy = $legacy = $noAlt = $srcset = $asyncDecode = 0;
        foreach ($images as $img) {
            /** @var \DOMElement $img */
            if ('' === $img->getAttribute('width') || '' === $img->getAttribute('height')) {
                ++$noDim;
            }
            if ('lazy' !== strtolower($img->getAttribute('loading'))) {
                ++$noLazy;
            }
            if (preg_match('/\.(jpe?g|png|gif)(\?.*)?$/i', $img->getAttribute('src'))) {
                ++$legacy;
            }
            if (!$img->hasAttribute('alt')) {
                ++$noAlt;
            }
            if ($img->hasAttribute('srcset')) {
                ++$srcset;
            }
            if ('async' === strtolower($img->getAttribute('decoding'))) {
                ++$asyncDecode;
            }
        }
        $picture = $this->count($xpath, '//picture');
        $iframeNoLazy = 0;
        foreach ($xpath->query('//iframe') as $iframe) {
            /** @var \DOMElement $iframe */
            if ('lazy' !== strtolower($iframe->getAttribute('loading'))) {
                ++$iframeNoLazy;
            }
        }
        $bgImages = preg_match_all('/background-image\s*:\s*url\(/i', $html);

        return [
            $this->f('img-dimensions', $noDim > 0 ? 'high' : 'ok', 'Images sans dimensions (width/height)', $noDim.' / '.$total,
                $noDim > 0 ? 'Ajoutez width/height (ou aspect-ratio) pour éviter les décalages de mise en page (CLS).' : ''),
            $this->f('img-lazy', $this->sev($noLazy, 0, 3), 'Images sans lazy-load', $noLazy.' / '.$total,
                $noLazy > 0 ? 'Activez loading="lazy" sur les images hors écran (sauf visuel principal de la 1ʳᵉ zone).' : ''),
            $this->f('img-format', $legacy > 0 ? 'medium' : 'ok', 'Formats anciens (jpg/png/gif)', $legacy.' / '.$total,
                $legacy > 0 ? 'Servez des formats modernes (WebP/AVIF) pour réduire le poids.' : ''),
            $this->f('img-alt', $noAlt > 0 ? 'medium' : 'ok', "Images sans attribut alt", $noAlt.' / '.$total,
                $noAlt > 0 ? 'Ajoutez un attribut alt (vide si décoratif) pour le SEO et l’accessibilité.' : ''),
            $this->f('img-srcset', 'info', 'Images responsive (srcset)', $srcset.' / '.$total,
                $total > 0 && 0 === $srcset ? 'Utilisez srcset/sizes pour servir des tailles adaptées à chaque écran.' : ''),
            $this->f('img-decoding', 'info', 'Décodage asynchrone (decoding="async")', $asyncDecode.' / '.$total, ''),
            $this->f('picture', 'info', 'Balises <picture>', (string) $picture, ''),
            $this->f('bg-images', 'info', 'Images de fond inline (background-image)', (string) $bgImages,
                $bgImages > 5 ? 'Nombreuses images de fond inline : elles ne bénéficient ni du lazy-load ni du srcset.' : ''),
            $this->f('iframe-lazy', $iframeNoLazy > 0 ? 'medium' : 'ok', 'Iframes sans lazy-load', (string) $iframeNoLazy,
                $iframeNoLazy > 0 ? 'Ajoutez loading="lazy" sur les iframes (vidéos, cartes).' : ''),
        ];
    }

    /**
     * Structure & DOM depth.
     *
     * @return array<int, array<string, mixed>>
     */
    private function structure(\DOMXPath $xpath, int $maxDepth, int $maxChildren): array
    {
        $h1 = $this->count($xpath, '//h1');
        $headings = [];
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            $headings[$tag] = $this->count($xpath, '//'.$tag);
        }
        $headingResume = implode(' · ', array_map(static fn ($t, $n) => strtoupper($t).':'.$n, array_keys($headings), $headings));
        $deprecated = $this->count($xpath, '//center | //font | //marquee | //blink | //big | //tt');
        $emptyContainers = $this->count($xpath, '//div[not(node())] | //span[not(node())]');

        return [
            $this->f('h1', 1 === $h1 ? 'ok' : ($h1 > 1 ? 'medium' : 'high'), 'Titre principal (H1)', $h1.' H1',
                1 === $h1 ? '' : (0 === $h1 ? 'Aucun H1 : ajoutez un titre principal unique.' : 'Plusieurs H1 : conservez un seul H1 par page.')),
            $this->f('headings', 'info', 'Hiérarchie des titres', $headingResume, ''),
            $this->f('dom-depth', $this->sev($maxDepth, 25, 32), 'Profondeur maximale du DOM', $maxDepth.' niveaux',
                $maxDepth > 25 ? 'DOM trop imbriqué : aplatissez la structure (Lighthouse alerte au-delà de 32 niveaux).' : ''),
            $this->f('dom-children', $this->sev($maxChildren, 40, 60), 'Enfants max sur un même élément', (string) $maxChildren,
                $maxChildren > 40 ? 'Un élément a beaucoup d’enfants directs : paginez ou virtualisez les longues listes.' : ''),
            $this->f('deprecated', $deprecated > 0 ? 'medium' : 'ok', 'Balises obsolètes', (string) $deprecated,
                $deprecated > 0 ? 'Supprimez les balises obsolètes (center, font, marquee…) au profit du CSS.' : ''),
            $this->f('empty-containers', $this->sev($emptyContainers, 10, 30), 'Conteneurs vides (div/span)', (string) $emptyContainers,
                $emptyContainers > 10 ? 'Nombreux conteneurs vides : nettoyez le markup généré pour alléger le DOM.' : ''),
        ];
    }

    /**
     * SEO & metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    private function seo(\DOMXPath $xpath): array
    {
        $title = $this->text($xpath, '//head/title');
        $titleLen = mb_strlen($title);
        $descNode = $xpath->query('//head/meta[@name="description"]/@content')->item(0);
        $desc = $descNode ? trim($descNode->nodeValue) : '';
        $descLen = mb_strlen($desc);
        $canonical = $this->count($xpath, '//head/link[@rel="canonical"]');
        $robots = strtolower($this->attr($xpath, '//head/meta[@name="robots"]/@content'));
        $noindex = str_contains($robots, 'noindex');
        $ogTitle = $this->count($xpath, '//head/meta[@property="og:title"]');
        $ogImage = $this->count($xpath, '//head/meta[@property="og:image"]');
        $twitter = $this->count($xpath, '//head/meta[starts-with(@name,"twitter:")]');
        $hreflang = $this->count($xpath, '//head/link[@rel="alternate"][@hreflang]');
        $structured = $this->count($xpath, '//script[@type="application/ld+json"]');
        $links = $this->count($xpath, '//a[@href]');

        return [
            $this->f('title', '' === $title ? 'high' : (($titleLen < 10 || $titleLen > 65) ? 'medium' : 'ok'), 'Balise <title>',
                '' === $title ? 'absente' : $titleLen.' car.',
                '' === $title ? 'Ajoutez une balise title.' : (($titleLen < 10 || $titleLen > 65) ? 'Visez 10 à 65 caractères.' : '')),
            $this->f('description', '' === $desc ? 'medium' : (($descLen < 50 || $descLen > 160) ? 'low' : 'ok'), 'Meta description',
                '' === $desc ? 'absente' : $descLen.' car.',
                '' === $desc ? 'Ajoutez une meta description.' : (($descLen < 50 || $descLen > 160) ? 'Visez 50 à 160 caractères.' : '')),
            $this->f('canonical', $canonical > 0 ? 'ok' : 'low', 'URL canonique', $canonical > 0 ? 'présente' : 'absente',
                0 === $canonical ? 'Ajoutez une balise canonical pour éviter le contenu dupliqué.' : ''),
            $this->f('noindex', $noindex ? 'medium' : 'ok', 'Indexation (meta robots)', $noindex ? 'noindex' : 'indexable',
                $noindex ? 'La page est en noindex : normal en preview, à vérifier avant publication.' : ''),
            $this->f('og', ($ogTitle > 0 && $ogImage > 0) ? 'ok' : 'low', 'Open Graph (partage social)', 'title:'.$ogTitle.' image:'.$ogImage,
                ($ogTitle > 0 && $ogImage > 0) ? '' : 'Complétez og:title et og:image pour le partage sur les réseaux.'),
            $this->f('twitter', 'info', 'Twitter Card', (string) $twitter, ''),
            $this->f('hreflang', 'info', 'Alternates hreflang', (string) $hreflang, ''),
            $this->f('structured-data', $structured > 0 ? 'ok' : 'info', 'Données structurées (JSON-LD)', (string) $structured,
                0 === $structured ? 'Ajoutez du JSON-LD (schema.org) pour enrichir les résultats de recherche.' : ''),
            $this->f('links', 'info', 'Liens <a>', (string) $links, ''),
        ];
    }

    /**
     * Accessibility.
     *
     * @return array<int, array<string, mixed>>
     */
    private function accessibility(\DOMXPath $xpath): array
    {
        $lang = $this->attr($xpath, '//html/@lang');
        $emptyLinks = 0;
        foreach ($xpath->query('//a[@href]') as $a) {
            /** @var \DOMElement $a */
            if ('' === trim($a->textContent) && '' === $a->getAttribute('aria-label') && '' === $a->getAttribute('title')
                && 0 === $xpath->query('.//img[@alt!=""] | .//*[@aria-label]', $a)->length) {
                ++$emptyLinks;
            }
        }
        $emptyButtons = 0;
        foreach ($xpath->query('//button') as $b) {
            /** @var \DOMElement $b */
            if ('' === trim($b->textContent) && '' === $b->getAttribute('aria-label') && '' === $b->getAttribute('title')) {
                ++$emptyButtons;
            }
        }
        $unlabeled = 0;
        foreach ($xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button")] | //select | //textarea') as $field) {
            /** @var \DOMElement $field */
            $id = $field->getAttribute('id');
            $hasLabel = '' !== $id && $xpath->query(sprintf('//label[@for="%s"]', $id))->length > 0;
            if (!$hasLabel && '' === $field->getAttribute('aria-label') && '' === $field->getAttribute('aria-labelledby') && '' === $field->getAttribute('title')) {
                ++$unlabeled;
            }
        }
        $tabindexHigh = $this->count($xpath, '//*[@tabindex > 0]');
        $landmarks = $this->count($xpath, '//main | //nav | //header | //footer | //*[@role="main"]');

        return [
            $this->f('lang', '' !== $lang ? 'ok' : 'high', 'Langue du document (<html lang>)', '' !== $lang ? $lang : 'absente',
                '' === $lang ? 'Ajoutez l’attribut lang sur <html> pour les lecteurs d’écran et le SEO.' : ''),
            $this->f('empty-links', $emptyLinks > 0 ? 'medium' : 'ok', 'Liens sans intitulé', (string) $emptyLinks,
                $emptyLinks > 0 ? 'Ajoutez un texte visible ou un aria-label sur ces liens.' : ''),
            $this->f('empty-buttons', $emptyButtons > 0 ? 'medium' : 'ok', 'Boutons sans intitulé', (string) $emptyButtons,
                $emptyButtons > 0 ? 'Ajoutez un texte ou un aria-label sur ces boutons (icônes seules).' : ''),
            $this->f('unlabeled-fields', $unlabeled > 0 ? 'medium' : 'ok', 'Champs de formulaire sans label', (string) $unlabeled,
                $unlabeled > 0 ? 'Associez un <label for> ou un aria-label à chaque champ.' : ''),
            $this->f('tabindex', $tabindexHigh > 0 ? 'low' : 'ok', 'tabindex positifs', (string) $tabindexHigh,
                $tabindexHigh > 0 ? 'Évitez tabindex > 0 : il casse l’ordre naturel de navigation au clavier.' : ''),
            $this->f('landmarks', $landmarks > 0 ? 'ok' : 'low', 'Repères de structure (main/nav/header/footer)', (string) $landmarks,
                0 === $landmarks ? 'Ajoutez des balises sémantiques (main, nav, header, footer).' : ''),
        ];
    }

    /**
     * Best practices & security.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bestPractices(\DOMXPath $xpath, string $html): array
    {
        $viewport = $this->count($xpath, '//head/meta[@name="viewport"]');
        $charset = $this->count($xpath, '//head/meta[@charset] | //head/meta[contains(translate(@http-equiv,"CT","ct"),"content-type")]');
        $favicon = $this->count($xpath, '//head/link[contains(@rel,"icon")]');
        $inlineHandlers = $this->count($xpath, '//*[@onclick or @onload or @onchange or @onsubmit or @onmouseover or @onerror or @onkeydown or @onkeyup or @onfocus or @onblur]');
        $inlineStyles = $this->count($xpath, '//*[@style]');

        $blankUnsafe = 0;
        foreach ($xpath->query('//a[@target="_blank"]') as $a) {
            /** @var \DOMElement $a */
            $rel = strtolower($a->getAttribute('rel'));
            if (!str_contains($rel, 'noopener') && !str_contains($rel, 'noreferrer')) {
                ++$blankUnsafe;
            }
        }
        $httpAssets = preg_match_all('#(?:src|href)\s*=\s*["\']http://#i', $html);
        $documentWrite = preg_match_all('/document\.write\s*\(/i', $html);

        return [
            $this->f('viewport', $viewport > 0 ? 'ok' : 'high', 'Meta viewport (responsive)', $viewport > 0 ? 'présent' : 'absent',
                0 === $viewport ? 'Ajoutez <meta name="viewport"> : indispensable pour le rendu mobile.' : ''),
            $this->f('charset', $charset > 0 ? 'ok' : 'medium', 'Déclaration du charset', $charset > 0 ? 'présent' : 'absent',
                0 === $charset ? 'Déclarez <meta charset="utf-8"> en tête du <head>.' : ''),
            $this->f('favicon', $favicon > 0 ? 'ok' : 'low', 'Favicon', $favicon > 0 ? 'présent' : 'absent',
                0 === $favicon ? 'Ajoutez un favicon (link rel="icon").' : ''),
            $this->f('inline-styles', $this->sev($inlineStyles, 30, 80), 'Styles inline (attribut style)', (string) $inlineStyles,
                $inlineStyles > 30 ? 'Nombreux styles inline : préférez des classes (HTML plus léger, meilleur cache, CSP).' : ''),
            $this->f('inline-handlers', $inlineHandlers > 0 ? 'medium' : 'ok', 'Gestionnaires d’événements inline (onclick…)', (string) $inlineHandlers,
                $inlineHandlers > 0 ? 'Déportez les handlers en JS : meilleur pour la maintenance et une CSP stricte.' : ''),
            $this->f('blank-noopener', $blankUnsafe > 0 ? 'medium' : 'ok', 'Liens target="_blank" sans rel="noopener"', (string) $blankUnsafe,
                $blankUnsafe > 0 ? 'Ajoutez rel="noopener" (sécurité, évite l’accès à window.opener).' : ''),
            $this->f('http-assets', $httpAssets > 0 ? 'high' : 'ok', 'Ressources en HTTP (non sécurisé)', (string) $httpAssets,
                $httpAssets > 0 ? 'Servez toutes les ressources en HTTPS pour éviter le contenu mixte (mixed content).' : ''),
            $this->f('document-write', $documentWrite > 0 ? 'medium' : 'ok', 'Usage de document.write()', (string) $documentWrite,
                $documentWrite > 0 ? 'Évitez document.write() : il bloque le parsing et dégrade les performances.' : ''),
        ];
    }

    /**
     * Compute max DOM depth and max direct element children in a single pass.
     *
     * @return array{0: int, 1: int}
     */
    private function domShape(\DOMDocument $dom): array
    {
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return [0, 0];
        }
        $maxDepth = 0;
        $maxChildren = 0;
        $walk = static function (\DOMNode $node, int $depth) use (&$walk, &$maxDepth, &$maxChildren): void {
            $children = 0;
            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    ++$children;
                    $walk($child, $depth + 1);
                }
            }
            $maxDepth = max($maxDepth, $depth);
            $maxChildren = max($maxChildren, $children);
        };
        $walk($body, 0);

        return [$maxDepth, $maxChildren];
    }

    /**
     * Approximate number of HTTP requests implied by the markup.
     */
    private function requestCount(\DOMXPath $xpath): int
    {
        $count = 0;
        $count += $this->count($xpath, '//script[@src]');
        $count += $this->count($xpath, '//link[@rel="stylesheet"]');
        $count += $this->count($xpath, '//link[@rel="preload"]');
        $count += $this->count($xpath, '//link[contains(@rel,"icon")]');
        $count += $this->count($xpath, '//iframe[@src]');
        $count += $this->count($xpath, '//source[@src or @srcset]');
        foreach ($xpath->query('//img[@src]') as $img) {
            /** @var \DOMElement $img */
            if (!str_starts_with(strtolower(trim($img->getAttribute('src'))), 'data:')) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Distinct external hosts referenced by src/href absolute URLs, excluding the
     * page's own host.
     *
     * @return array<int, string>
     */
    private function externalHosts(\DOMXPath $xpath, ?string $ownHost = null): array
    {
        $stripWww = static fn (string $host): string => str_starts_with($host, 'www.') ? substr($host, 4) : $host;
        $own = $ownHost ? $stripWww(strtolower($ownHost)) : null;
        $hosts = [];
        foreach ($xpath->query('//*[@src]/@src | //*[@href]/@href') as $attr) {
            $value = trim($attr->nodeValue);
            if (1 === preg_match('#^https?://#i', $value)) {
                $host = parse_url($value, PHP_URL_HOST);
                if (is_string($host) && '' !== $host) {
                    $host = strtolower($host);
                    // Exclude the page's own host (ignoring a leading "www.").
                    if (null !== $own && $stripWww($host) === $own) {
                        continue;
                    }
                    $hosts[$host] = true;
                }
            }
        }

        return array_keys($hosts);
    }

    /**
     * Derive the page's own host from its canonical link or og:url.
     */
    private function ownHostFromHtml(\DOMXPath $xpath): ?string
    {
        foreach (['//head/link[@rel="canonical"]/@href', '//head/meta[@property="og:url"]/@content'] as $query) {
            $value = $this->attr($xpath, $query);
            if ('' !== $value) {
                $host = parse_url($value, PHP_URL_HOST);
                if (is_string($host) && '' !== $host) {
                    return strtolower($host);
                }
            }
        }

        return null;
    }

    private function count(\DOMXPath $xpath, string $query): int
    {
        $nodes = $xpath->query($query);

        return false === $nodes ? 0 : $nodes->length;
    }

    private function text(\DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);

        return $node ? trim($node->textContent) : '';
    }

    private function attr(\DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);

        return $node ? trim($node->nodeValue) : '';
    }

    private function kb(int $bytes): string
    {
        return ((int) round($bytes / 1024)).' Ko';
    }

    /**
     * @return 'high'|'medium'|'ok'
     */
    private function sev(int $value, int $low, int $high): string
    {
        if ($value > $high) {
            return 'high';
        }
        if ($value > $low) {
            return 'medium';
        }

        return 'ok';
    }

    /**
     * @return array<string, mixed>
     */
    private function f(string $id, string $severity, string $label, string $value, string $reco): array
    {
        return ['id' => $id, 'severity' => $severity, 'label' => $label, 'value' => $value, 'reco' => $reco];
    }

    /**
     * @param array<int, array<string, mixed>> $findings
     *
     * @return array{high: int, medium: int, low: int}
     */
    private function groupCounts(array $findings): array
    {
        $counts = ['high' => 0, 'medium' => 0, 'low' => 0];
        foreach ($findings as $finding) {
            if (isset($counts[$finding['severity']])) {
                ++$counts[$finding['severity']];
            }
        }

        return $counts;
    }

    /**
     * Indicative heuristic score (0-100), NOT a measured Lighthouse score.
     *
     * @param array<int, array<string, mixed>> $findings
     */
    private function score(array $findings): int
    {
        $penalty = ['high' => 12, 'medium' => 5, 'low' => 1, 'ok' => 0, 'info' => 0];
        $score = 100;
        foreach ($findings as $finding) {
            $score -= $penalty[$finding['severity']] ?? 0;
        }

        return max(0, min(100, $score));
    }

    /**
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
