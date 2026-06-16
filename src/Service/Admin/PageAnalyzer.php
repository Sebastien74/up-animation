<?php

declare(strict_types=1);

namespace App\Service\Admin;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    /**
     * Category weights for the calibrated score (sum is normalized).
     */
    private const SCORE_WEIGHTS = [
        'perf' => 30,
        'resources' => 15,
        'images' => 15,
        'seo' => 15,
        'structure' => 10,
        'a11y' => 10,
        'best' => 5,
    ];

    /**
     * Per-finding health penalty inside a category (capped at 0 per category).
     */
    private const HEALTH_PENALTY = ['high' => 0.34, 'medium' => 0.15, 'low' => 0.05];

    /**
     * Max example elements collected per finding (to point the admin at the exact
     * offending nodes without bloating the stored report).
     */
    private const MAX_SAMPLES = 6;

    /**
     * Max exact DOM paths kept per empty-container group (the breakdown stays useful
     * yet bounded even on pathological pages); the true count is reported separately.
     */
    private const MAX_PATHS_PER_GROUP = 25;

    public function __construct(
        private readonly TranslatorInterface $translator,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir = '',
    ) {
    }

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

        // Preview mode injects the in-context editor ("webmaster") controls: drop every
        // element pointing at an /admin-<token>/ path so they never skew the analysis.
        $this->stripAdminControls($xpath);

        [$maxDepth, $maxChildren] = $this->domShape($dom);
        $domCount = $this->count($xpath, '//body//*');
        $ownHost = $ownHost ? strtolower($ownHost) : $this->ownHostFromHtml($xpath);

        $meta['dom'] = $domCount;
        $meta['images'] = $this->count($xpath, '//img');
        $meta['scripts'] = $this->count($xpath, '//script[@src]');
        $meta['requests'] = $this->requestCount($xpath);
        $meta['externalDomains'] = count($this->externalHosts($xpath, $ownHost));
        $meta['ogImage'] = $this->attr($xpath, '//head/meta[@property="og:image"]/@content');
        $meta['favicon'] = $this->attr($xpath, '//head/link[contains(@rel,"icon")][@href]/@href');

        $groups = [
            ['id' => 'perf', 'label' => 'Performance & rendu', 'findings' => $this->perf($xpath, $html, $bytes, $domCount, $maxDepth, $maxChildren)],
            ['id' => 'resources', 'label' => 'Ressources & chargement', 'findings' => $this->resources($xpath, $html, $ownHost)],
            ['id' => 'images', 'label' => 'Images & médias', 'findings' => $this->images($xpath, $html, $ownHost)],
            ['id' => 'structure', 'label' => 'Structure & DOM', 'findings' => $this->structure($xpath, $maxDepth, $maxChildren)],
            ['id' => 'seo', 'label' => 'SEO & métadonnées', 'findings' => $this->seo($xpath)],
            ['id' => 'a11y', 'label' => 'Accessibilité', 'findings' => $this->accessibility($xpath)],
            ['id' => 'best', 'label' => 'Bonnes pratiques & sécurité', 'findings' => $this->bestPractices($xpath, $html)],
        ];

        $all = [];
        foreach ($groups as &$group) {
            // SEO keeps a fixed editorial order (title, description, canonical, …); every
            // other group is ordered by severity so problems surface first.
            $group['findings'] = 'seo' === $group['id'] ? $group['findings'] : $this->sort($group['findings']);
            $group['counts'] = $this->groupCounts($group['findings']);
            $all = array_merge($all, $group['findings']);
        }
        unset($group);

        return [
            'meta' => $meta,
            'score' => $this->score($groups),
            'summary' => $this->groupCounts($all),
            'groups' => $groups,
        ];
    }

    public function httpError(int $status, ?string $detail = null): array
    {
        $reco = null !== $detail && '' !== $detail
            ? $detail
            : $this->translator->trans('La page a renvoyé une erreur serveur : corrigez-la avant de l’analyser (consultez les logs ou le profiler).', [], 'admin');

        return [
            'meta' => [
                'urlCode' => null,
                'bytes' => 0,
                'kb' => 0,
                'dom' => 0,
                'images' => 0,
                'scripts' => 0,
                'requests' => 0,
                'httpStatus' => $status,
            ],
            'score' => null,
            'summary' => ['high' => 1, 'medium' => 0, 'low' => 0],
            'groups' => [[
                'id' => 'error',
                'label' => $this->translator->trans('Erreur', [], 'admin'),
                'counts' => ['high' => 1, 'medium' => 0, 'low' => 0],
                'findings' => [[
                    'id' => 'http-error',
                    'severity' => 'high',
                    'label' => $this->translator->trans('Erreur de rendu de la page', [], 'admin'),
                    'value' => 'HTTP '.$status,
                    'reco' => $reco,
                    'samples' => [],
                ]],
            ]],
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
        $blockingScriptEls = [];
        foreach ($xpath->query('//head//script[@src]') as $script) {
            /** @var \DOMElement $script */
            if (!$script->hasAttribute('async') && !$script->hasAttribute('defer') && 'module' !== strtolower($script->getAttribute('type'))) {
                ++$blockingScripts;
                $this->sample($blockingScriptEls, $script);
            }
        }
        $totalScripts = $this->count($xpath, '//script[@src]');

        $blockingCss = 0;
        $blockingCssEls = [];
        foreach ($xpath->query('//head//link[@rel="stylesheet"]') as $link) {
            /** @var \DOMElement $link */
            if ('print' !== strtolower($link->getAttribute('media'))) {
                ++$blockingCss;
                $this->sample($blockingCssEls, $link);
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
                $blockingScripts > 0 ? 'Ajoutez defer/async ou déplacez ces scripts en fin de <body> (réduit le TBT).' : '', $blockingScriptEls, $blockingScripts),
            $this->f('css-blocking', $this->sev($blockingCss, 1, 3), 'Feuilles de style bloquantes', (string) $blockingCss,
                $blockingCss > 1 ? 'Inlinez le CSS critique et chargez le reste en asynchrone (preload + onload).' : '', $blockingCssEls, $blockingCss),
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
                count($external) > 4 ? 'Chaque domaine tiers ajoute une résolution DNS + handshake : limitez-les ou préconnectez-vous.' : '',
                array_slice($external, 0, self::MAX_SAMPLES), count($external)),
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
    private function images(\DOMXPath $xpath, string $html, ?string $ownHost = null): array
    {
        $images = $xpath->query('//img');
        $total = $images->length;
        $noDim = $noLazy = $legacy = $noAlt = $srcset = $asyncDecode = $noVariants = 0;
        $noDimEls = $noLazyEls = $legacyEls = $noAltEls = $noVariantEls = [];
        foreach ($images as $img) {
            /** @var \DOMElement $img */
            // Real source: lazy images keep a data: placeholder in src and the file in data-src.
            $realSrc = trim($img->getAttribute('src'));
            if ('' === $realSrc || 0 === stripos($realSrc, 'data:')) {
                $realSrc = trim($img->getAttribute('data-src'));
            }
            // Lazy-loaded either natively (loading="lazy") or via the project's JS loader
            // (data-src + a "lazy" class), so JS-lazy images are not flagged as eager.
            $lazyLoaded = 'lazy' === strtolower($img->getAttribute('loading'))
                || $img->hasAttribute('data-src')
                || str_contains(strtolower($img->getAttribute('class')), 'lazy');

            if ('' === $img->getAttribute('width') || '' === $img->getAttribute('height')) {
                ++$noDim;
                $this->sample($noDimEls, $img);
            }
            if (!$lazyLoaded) {
                ++$noLazy;
                $this->sample($noLazyEls, $img);
            }
            if (preg_match('/\.(jpe?g|png|gif)(\?.*)?$/i', $realSrc)) {
                ++$legacy;
                $this->sample($legacyEls, $img);
            }
            if (!$img->hasAttribute('alt')) {
                ++$noAlt;
                $this->sample($noAltEls, $img);
            }
            if ($img->hasAttribute('srcset')) {
                ++$srcset;
            }
            // Responsive coverage per device: a single src (no srcset, not in <picture>)
            // serves the same file to Desktop, Laptop, Tablet and Mobile alike.
            $inPicture = $img->parentNode instanceof \DOMElement && 'picture' === strtolower($img->parentNode->nodeName);
            if (!$img->hasAttribute('srcset') && !$inPicture) {
                ++$noVariants;
                $this->sample($noVariantEls, $img);
            }
            if ('async' === strtolower($img->getAttribute('decoding'))) {
                ++$asyncDecode;
            }
        }

        $weight = $this->imageWeights($xpath, $ownHost);
        $weightKb = (int) round($weight['bytes'] / 1024);
        $heaviest = [];
        foreach (\array_slice($weight['files'], 0, self::MAX_SAMPLES) as $file) {
            $heaviest[] = $file['label'].' - '.((int) round($file['bytes'] / 1024)).' Ko';
        }
        $picture = $this->count($xpath, '//picture');
        $iframeNoLazy = 0;
        $iframeEls = [];
        foreach ($xpath->query('//iframe') as $iframe) {
            /** @var \DOMElement $iframe */
            if ('lazy' !== strtolower($iframe->getAttribute('loading'))) {
                ++$iframeNoLazy;
                $this->sample($iframeEls, $iframe);
            }
        }
        [$bgImages, $bgBreakdown] = $this->inlineBackgrounds($xpath);
        $bgFinding = $this->f('bg-images', 'info', 'Images de fond inline (background-image)', (string) $bgImages,
            $bgImages > 5 ? 'Nombreuses images de fond inline : elles ne bénéficient ni du lazy-load ni du srcset.' : '', [], $bgImages);
        $bgFinding['breakdown'] = $bgBreakdown;

        return [
            $this->f('img-dimensions', $noDim > 0 ? 'high' : 'ok', 'Images sans dimensions (width/height)', $noDim.' / '.$total,
                $noDim > 0 ? 'Ajoutez width/height (ou aspect-ratio) pour éviter les décalages de mise en page (CLS).' : '', $noDimEls, $noDim),
            $this->f('img-lazy', $this->sev($noLazy, 0, 3), 'Images sans lazy-load', $noLazy.' / '.$total,
                $noLazy > 0 ? 'Activez loading="lazy" sur les images hors écran (sauf visuel principal de la 1ʳᵉ zone).' : '', $noLazyEls, $noLazy),
            $this->f('img-format', $legacy > 0 ? 'medium' : 'ok', 'Formats anciens (jpg/png/gif)', $legacy.' / '.$total,
                $legacy > 0 ? 'Servez des formats modernes (WebP/AVIF) pour réduire le poids.' : '', $legacyEls, $legacy),
            $this->f('img-alt', $noAlt > 0 ? 'medium' : 'ok', "Images sans attribut alt", $noAlt.' / '.$total,
                $noAlt > 0 ? 'Ajoutez un attribut alt (vide si décoratif) pour le SEO et l’accessibilité.' : '', $noAltEls, $noAlt),
            $this->f('img-weight', $this->sev($weightKb, 1000, 2500), 'Poids des images (fichiers locaux)',
                $weightKb.' Ko ('.$weight['count'].')',
                $weightKb > 1000 ? 'Compressez et redimensionnez les images les plus lourdes (formats modernes, dimensions adaptées).' : '',
                $heaviest, $weight['count']),
            $this->f('img-variants', $noVariants > 0 ? 'medium' : 'ok', 'Images sans variantes responsive (par écran)', $noVariants.' / '.$total,
                $noVariants > 0 ? 'Servez des variantes adaptées à chaque écran (srcset/sizes ou <picture>) : Desktop, Laptop, Tablette, Mobile.' : '', $noVariantEls, $noVariants),
            $this->f('img-srcset', 'info', 'Images responsive (srcset)', $srcset.' / '.$total,
                $total > 0 && 0 === $srcset ? 'Utilisez srcset/sizes pour servir des tailles adaptées à chaque écran.' : ''),
            $this->f('img-decoding', 'info', 'Décodage asynchrone (decoding="async")', $asyncDecode.' / '.$total, ''),
            $this->f('picture', 'info', 'Balises <picture>', (string) $picture, ''),
            $bgFinding,
            $this->f('iframe-lazy', $iframeNoLazy > 0 ? 'medium' : 'ok', 'Iframes sans lazy-load', (string) $iframeNoLazy,
                $iframeNoLazy > 0 ? 'Ajoutez loading="lazy" sur les iframes (vidéos, cartes).' : '', $iframeEls, $iframeNoLazy),
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
        $h1Texts = [];
        foreach ($xpath->query('//h1') as $node) {
            $text = trim((string) preg_replace('/\s+/', ' ', (string) $node->textContent));
            if ('' !== $text && count($h1Texts) < self::MAX_SAMPLES) {
                $h1Texts[] = $text;
            }
        }
        $headings = [];
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            $headings[$tag] = $this->count($xpath, '//'.$tag);
        }
        $headingResume = implode(' · ', array_map(static fn ($t, $n) => strtoupper($t).':'.$n, array_keys($headings), $headings));
        $deprecated = 0;
        $deprecatedEls = [];
        foreach ($xpath->query('//center | //font | //marquee | //blink | //big | //tt') as $node) {
            /** @var \DOMElement $node */
            ++$deprecated;
            $this->sample($deprecatedEls, $node);
        }
        [$emptyContainers, $emptyBreakdown] = $this->emptyContainers($xpath);
        $emptyFinding = $this->f('empty-containers', $this->sev($emptyContainers, 10, 30), 'Conteneurs vides (div/span)', (string) $emptyContainers,
            $emptyContainers > 10 ? 'Nombreux conteneurs vides : nettoyez le markup généré pour alléger le DOM.' : '', [], $emptyContainers);
        $emptyFinding['breakdown'] = $emptyBreakdown;

        return [
            $this->f('h1', 1 === $h1 ? 'ok' : ($h1 > 1 ? 'medium' : 'high'), 'Titre principal (H1)', $h1.' H1',
                1 === $h1 ? '' : (0 === $h1 ? 'Aucun H1 : ajoutez un titre principal unique.' : 'Plusieurs H1 : conservez un seul H1 par page.'), $h1Texts, $h1),
            $this->f('headings', 'info', 'Hiérarchie des titres', $headingResume, ''),
            $this->f('dom-depth', $this->sev($maxDepth, 25, 32), 'Profondeur maximale du DOM', $maxDepth.' niveaux',
                $maxDepth > 25 ? 'DOM trop imbriqué : aplatissez la structure (Lighthouse alerte au-delà de 32 niveaux).' : ''),
            $this->f('dom-children', $this->sev($maxChildren, 40, 60), 'Enfants max sur un même élément', (string) $maxChildren,
                $maxChildren > 40 ? 'Un élément a beaucoup d’enfants directs : paginez ou virtualisez les longues listes.' : ''),
            $this->f('deprecated', $deprecated > 0 ? 'medium' : 'ok', 'Balises obsolètes', (string) $deprecated,
                $deprecated > 0 ? 'Supprimez les balises obsolètes (center, font, marquee…) au profit du CSS.' : '', $deprecatedEls, $deprecated),
            $emptyFinding,
        ];
    }

    /**
     * Empty div/span containers grouped by signature (tag + classes), each with its
     * exact DOM path(s), so the admin can locate every occurrence and its frequency.
     *
     * @return array{0: int, 1: array<int, array{signature: string, count: int, paths: array<int, string>}>}
     */
    private function emptyContainers(\DOMXPath $xpath): array
    {
        $groups = [];
        $total = 0;
        foreach ($xpath->query('//div[not(node())] | //span[not(node())]') as $node) {
            /** @var \DOMElement $node */
            ++$total;
            $signature = $this->containerSignature($node);
            if (!isset($groups[$signature])) {
                $groups[$signature] = ['signature' => $signature, 'count' => 0, 'paths' => []];
            }
            ++$groups[$signature]['count'];
            if (count($groups[$signature]['paths']) < self::MAX_PATHS_PER_GROUP) {
                $groups[$signature]['paths'][] = $this->domPath($node);
            }
        }
        usort($groups, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return [$total, array_values($groups)];
    }

    /**
     * Elements carrying an inline background image (style="…url(…)"), grouped by the
     * image URL, each with its occurrence count and exact DOM path(s).
     *
     * @return array{0: int, 1: array<int, array{signature: string, count: int, paths: array<int, string>}>}
     */
    private function inlineBackgrounds(\DOMXPath $xpath): array
    {
        $groups = [];
        $total = 0;
        foreach ($xpath->query('//*[contains(@style, "url(")]') as $node) {
            /** @var \DOMElement $node */
            if (!preg_match_all('/(?:background-image|background)\s*:[^;]*url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i', $node->getAttribute('style'), $matches, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($matches as $match) {
                $url = trim($match[2]);
                if (0 === stripos($url, 'data:')) {
                    $url = 'data:'.(strtok(substr($url, 5), ';,') ?: 'inline').' (inline)';
                }
                ++$total;
                $signature = 'url('.$url.')';
                if (!isset($groups[$signature])) {
                    $groups[$signature] = ['signature' => $signature, 'count' => 0, 'paths' => []];
                }
                ++$groups[$signature]['count'];
                if (count($groups[$signature]['paths']) < self::MAX_PATHS_PER_GROUP) {
                    $groups[$signature]['paths'][] = $this->domPath($node);
                }
            }
        }
        usort($groups, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return [$total, array_values($groups)];
    }

    /**
     * Group key for an empty container: tag plus its class list (ids stay out, so
     * identical structural blocks group together; the exact node is in its path).
     */
    private function containerSignature(\DOMElement $el): string
    {
        $tag = strtolower($el->nodeName);
        $class = trim($el->getAttribute('class'));

        return '' !== $class ? '<'.$tag.' class="'.$class.'">' : '<'.$tag.'>';
    }

    /**
     * CSS-like path from <body> to the element (id when present, else classes plus a
     * :nth-of-type when siblings of the same tag would otherwise be ambiguous).
     */
    private function domPath(\DOMElement $el): string
    {
        $segments = [];
        for ($node = $el; $node instanceof \DOMElement; $node = $node->parentNode) {
            $tag = strtolower($node->nodeName);
            if ('body' === $tag || 'html' === $tag) {
                break;
            }
            $segment = $tag;
            $id = trim($node->getAttribute('id'));
            if ('' !== $id) {
                $segment .= '#'.$id;
            } else {
                $class = trim($node->getAttribute('class'));
                if ('' !== $class) {
                    $segment .= '.'.implode('.', preg_split('/\s+/', $class) ?: []);
                }
                $nth = $this->nthOfType($node);
                if (null !== $nth) {
                    $segment .= ':nth-of-type('.$nth.')';
                }
            }
            array_unshift($segments, $segment);
        }

        return 'body > '.implode(' > ', $segments);
    }

    /**
     * 1-based position of $el among its same-tag siblings, or null when it is the
     * only element of its tag under its parent (no disambiguation needed).
     */
    private function nthOfType(\DOMElement $el): ?int
    {
        $tag = $el->nodeName;
        $position = 0;
        $sameType = 0;
        for ($sibling = $el->parentNode?->firstChild; null !== $sibling; $sibling = $sibling->nextSibling) {
            if ($sibling instanceof \DOMElement && $sibling->nodeName === $tag) {
                ++$sameType;
                if ($sibling === $el) {
                    $position = $sameType;
                }
            }
        }

        return $sameType > 1 ? $position : null;
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
        $canonicalHref = $this->attr($xpath, '//head/link[@rel="canonical"]/@href');
        $robots = strtolower($this->attr($xpath, '//head/meta[@name="robots"]/@content'));
        $noindex = str_contains($robots, 'noindex');
        $ogTitle = $this->count($xpath, '//head/meta[@property="og:title"]');
        $ogTitleValue = $this->attr($xpath, '//head/meta[@property="og:title"]/@content');
        $ogImage = $this->count($xpath, '//head/meta[@property="og:image"]');
        $ogImageValue = $this->attr($xpath, '//head/meta[@property="og:image"]/@content');
        $twitter = $this->count($xpath, '//head/meta[starts-with(@name,"twitter:")]');
        $hreflang = $this->count($xpath, '//head/link[@rel="alternate"][@hreflang]');
        $structured = 0;
        $structuredEls = [];
        $breadcrumbJsonLd = false;
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $node) {
            ++$structured;
            $raw = trim($node->textContent);
            if (str_contains($raw, 'BreadcrumbList')) {
                $breadcrumbJsonLd = true;
            }
            $decoded = json_decode($raw, true);
            $pretty = null !== $decoded
                ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : $raw;
            if (count($structuredEls) < self::MAX_SAMPLES) {
                $structuredEls[] = (string) $pretty;
            }
        }

        $breadcrumbMarkup = $this->count($xpath, '//nav[contains(translate(@aria-label, "BREADCRUMBFILDAIN", "breadcrumbfildain"), "breadcrumb")]'
            .' | //*[contains(concat(" ", normalize-space(@class), " "), " breadcrumb ")]'
            .' | //*[contains(@itemtype, "BreadcrumbList")]') > 0;
        $breadcrumb = $breadcrumbJsonLd || $breadcrumbMarkup;
        $breadcrumbSource = $breadcrumbJsonLd ? 'JSON-LD' : ($breadcrumbMarkup ? 'markup' : '');
        $links = $this->count($xpath, '//a[@href]');

        return [
            $this->f('title', '' === $title ? 'high' : (($titleLen < 10 || $titleLen > 65) ? 'medium' : 'ok'), 'Balise <title>',
                '' === $title ? 'absente' : $titleLen.' car.',
                '' === $title ? 'Ajoutez une balise title.' : (($titleLen < 10 || $titleLen > 65) ? 'Visez 10 à 65 caractères.' : ''),
                '' !== $title ? [$title] : []),
            $this->f('description', '' === $desc ? 'medium' : (($descLen < 50 || $descLen > 160) ? 'low' : 'ok'), 'Meta description',
                '' === $desc ? 'absente' : $descLen.' car.',
                '' === $desc ? 'Ajoutez une meta description.' : (($descLen < 50 || $descLen > 160) ? 'Visez 50 à 160 caractères.' : ''),
                '' !== $desc ? [$desc] : []),
            $this->f('canonical', $canonical > 0 ? 'ok' : 'low', 'URL canonique', $canonical > 0 ? 'présente' : 'absente',
                0 === $canonical ? 'Ajoutez une balise canonical pour éviter le contenu dupliqué.' : '',
                '' !== $canonicalHref ? [$canonicalHref] : []),
            $this->f('noindex', $noindex ? 'medium' : 'ok', 'Indexation (meta robots)', $noindex ? 'noindex' : 'indexable',
                $noindex ? 'La page est en noindex : normal en preview, à vérifier avant publication.' : '',
                '' !== $robots ? [$robots] : []),
            $this->f('og', ($ogTitle > 0 && $ogImage > 0) ? 'ok' : 'low', 'Open Graph (partage social)', 'title:'.$ogTitle.' image:'.$ogImage,
                ($ogTitle > 0 && $ogImage > 0) ? '' : 'Complétez og:title et og:image pour le partage sur les réseaux.',
                array_values(array_filter([$ogTitleValue, $ogImageValue]))),
            $this->f('twitter', 'info', 'Twitter Card', (string) $twitter, ''),
            $this->f('hreflang', 'info', 'Alternates hreflang', (string) $hreflang, ''),
            $this->f('structured-data', $structured > 0 ? 'ok' : 'info', 'Données structurées (JSON-LD)', (string) $structured,
                0 === $structured ? 'Ajoutez du JSON-LD (schema.org) pour enrichir les résultats de recherche.' : '', $structuredEls, $structured),
            $this->f('breadcrumb', $breadcrumb ? 'ok' : 'low', 'Fil d’Ariane (breadcrumb)', $breadcrumb ? 'présent ('.$breadcrumbSource.')' : 'absent',
                $breadcrumb ? '' : 'Ajoutez un fil d’Ariane (BreadcrumbList en JSON-LD ou markup nav) pour la navigation et les rich results.'),
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
        $emptyLinkEls = [];
        foreach ($xpath->query('//a[@href]') as $a) {
            /** @var \DOMElement $a */
            if ('' === trim($a->textContent) && '' === $a->getAttribute('aria-label') && '' === $a->getAttribute('title')
                && 0 === $xpath->query('.//img[@alt!=""] | .//*[@aria-label]', $a)->length) {
                ++$emptyLinks;
                $this->sample($emptyLinkEls, $a);
            }
        }
        $emptyButtons = 0;
        $emptyButtonEls = [];
        foreach ($xpath->query('//button') as $b) {
            /** @var \DOMElement $b */
            if ('' === trim($b->textContent) && '' === $b->getAttribute('aria-label') && '' === $b->getAttribute('title')) {
                ++$emptyButtons;
                $this->sample($emptyButtonEls, $b);
            }
        }
        $unlabeled = 0;
        $unlabeledEls = [];
        foreach ($xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button")] | //select | //textarea') as $field) {
            /** @var \DOMElement $field */
            $id = $field->getAttribute('id');
            $hasLabel = '' !== $id && $xpath->query(sprintf('//label[@for="%s"]', $id))->length > 0;
            if (!$hasLabel && '' === $field->getAttribute('aria-label') && '' === $field->getAttribute('aria-labelledby') && '' === $field->getAttribute('title')) {
                ++$unlabeled;
                $this->sample($unlabeledEls, $field);
            }
        }
        $tabindexHigh = $this->count($xpath, '//*[@tabindex > 0]');
        $landmarks = $this->count($xpath, '//main | //nav | //header | //footer | //*[@role="main"]');

        return [
            $this->f('lang', '' !== $lang ? 'ok' : 'high', 'Langue du document (<html lang>)', '' !== $lang ? $lang : 'absente',
                '' === $lang ? 'Ajoutez l’attribut lang sur <html> pour les lecteurs d’écran et le SEO.' : ''),
            $this->f('empty-links', $emptyLinks > 0 ? 'medium' : 'ok', 'Liens sans intitulé', (string) $emptyLinks,
                $emptyLinks > 0 ? 'Ajoutez un texte visible ou un aria-label sur ces liens.' : '', $emptyLinkEls, $emptyLinks),
            $this->f('empty-buttons', $emptyButtons > 0 ? 'medium' : 'ok', 'Boutons sans intitulé', (string) $emptyButtons,
                $emptyButtons > 0 ? 'Ajoutez un texte ou un aria-label sur ces boutons (icônes seules).' : '', $emptyButtonEls, $emptyButtons),
            $this->f('unlabeled-fields', $unlabeled > 0 ? 'medium' : 'ok', 'Champs de formulaire sans label', (string) $unlabeled,
                $unlabeled > 0 ? 'Associez un <label for> ou un aria-label à chaque champ.' : '', $unlabeledEls, $unlabeled),
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

        $inlineHandlers = 0;
        $inlineHandlerEls = [];
        foreach ($xpath->query('//*[@onclick or @onload or @onchange or @onsubmit or @onmouseover or @onerror or @onkeydown or @onkeyup or @onfocus or @onblur]') as $node) {
            /** @var \DOMElement $node */
            ++$inlineHandlers;
            $this->sample($inlineHandlerEls, $node);
        }
        $inlineStyles = 0;
        $inlineStyleEls = [];
        foreach ($xpath->query('//*[@style]') as $node) {
            /** @var \DOMElement $node */
            ++$inlineStyles;
            $this->sample($inlineStyleEls, $node);
        }

        $blankUnsafe = 0;
        $blankUnsafeEls = [];
        foreach ($xpath->query('//a[@target="_blank"]') as $a) {
            /** @var \DOMElement $a */
            $rel = strtolower($a->getAttribute('rel'));
            if (!str_contains($rel, 'noopener') && !str_contains($rel, 'noreferrer')) {
                ++$blankUnsafe;
                $this->sample($blankUnsafeEls, $a);
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
                $inlineStyles > 30 ? 'Nombreux styles inline : préférez des classes (HTML plus léger, meilleur cache, CSP).' : '', $inlineStyleEls, $inlineStyles),
            $this->f('inline-handlers', $inlineHandlers > 0 ? 'medium' : 'ok', 'Gestionnaires d’événements inline (onclick…)', (string) $inlineHandlers,
                $inlineHandlers > 0 ? 'Déportez les handlers en JS : meilleur pour la maintenance et une CSP stricte.' : '', $inlineHandlerEls, $inlineHandlers),
            $this->f('blank-noopener', $blankUnsafe > 0 ? 'medium' : 'ok', 'Liens target="_blank" sans rel="noopener"', (string) $blankUnsafe,
                $blankUnsafe > 0 ? 'Ajoutez rel="noopener" (sécurité, évite l’accès à window.opener).' : '', $blankUnsafeEls, $blankUnsafe),
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

    /**
     * Weight (bytes) of the page's locally-served images, read from the filesystem
     * (no network). External/CDN images are skipped; identical files counted once.
     *
     * @return array{bytes: int, count: int, files: array<int, array{label: string, bytes: int}>}
     */
    private function imageWeights(\DOMXPath $xpath, ?string $ownHost): array
    {
        $publicRoot = realpath($this->projectDir.'/public');
        if (false === $publicRoot) {
            return ['bytes' => 0, 'count' => 0, 'files' => []];
        }

        $stripWww = static fn (string $host): string => str_starts_with($host, 'www.') ? substr($host, 4) : $host;
        $own = $ownHost ? $stripWww(strtolower($ownHost)) : null;

        $bytes = 0;
        $files = [];
        $seen = [];
        foreach ($xpath->query('//img') as $img) {
            /** @var \DOMElement $img */
            // Lazy-loaded images keep a data: placeholder in src and the real file in
            // data-src: fall back to it so their weight is actually measured.
            $src = trim($img->getAttribute('src'));
            if ('' === $src || 0 === stripos($src, 'data:')) {
                $src = trim($img->getAttribute('data-src'));
            }
            if ('' === $src || 0 === stripos($src, 'data:')) {
                continue;
            }
            $path = (string) preg_replace('/[?#].*$/', '', $src);
            if (1 === preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
                $normalized = str_starts_with($path, '//') ? 'http:'.$path : $path;
                $host = parse_url($normalized, PHP_URL_HOST);
                if (!is_string($host) || null === $own || $stripWww(strtolower($host)) !== $own) {
                    continue;
                }
                $urlPath = (string) parse_url($normalized, PHP_URL_PATH);
            } elseif (str_starts_with($path, '/')) {
                $urlPath = $path;
            } else {
                continue;
            }

            $urlPath = rawurldecode($urlPath);
            $real = realpath($publicRoot.'/'.ltrim(str_replace('\\', '/', $urlPath), '/'));
            if (false === $real || !str_starts_with($real, $publicRoot) || !is_file($real) || isset($seen[$real])) {
                continue;
            }
            $seen[$real] = true;
            $size = (int) filesize($real);
            $bytes += $size;
            $files[] = ['label' => basename($urlPath), 'bytes' => $size];
        }

        usort($files, static fn (array $a, array $b): int => $b['bytes'] <=> $a['bytes']);

        return ['bytes' => $bytes, 'count' => count($files), 'files' => $files];
    }

    /**
     * Remove the preview-only admin/editor controls (links or forms targeting an
     * /admin-<token>/ path) so they are excluded from every count and finding.
     */
    private function stripAdminControls(\DOMXPath $xpath): void
    {
        $remove = [];
        foreach ($xpath->query('//a[@href] | //form[@action]') as $node) {
            /** @var \DOMElement $node */
            $target = $node->getAttribute('href').' '.$node->getAttribute('action');
            if (1 === preg_match('#/admin-[0-9a-f]{8,}/#i', $target)) {
                $remove[] = $node;
            }
        }
        foreach ($remove as $node) {
            $node->parentNode?->removeChild($node);
        }
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
     * Build a finding. Labels and recommendations are translated via the 'admin'
     * domain (the French text is used as the translation key).
     *
     * @return array<string, mixed>
     */
    private function f(string $id, string $severity, string $label, string $value, string $reco, array $samples = [], ?int $affected = null): array
    {
        $finding = [
            'id' => $id,
            'severity' => $severity,
            'label' => $this->translator->trans($label, [], 'admin'),
            'value' => $value,
            'reco' => '' === $reco ? '' : $this->translator->trans($reco, [], 'admin'),
            'samples' => array_values($samples),
        ];
        if (null !== $affected) {
            $finding['affected'] = $affected;
        }

        return $finding;
    }

    /**
     * Compact, human-readable identifier for a DOM element (tag + the most telling
     * attribute or its text), so a finding can point at the exact offending node.
     */
    private function describe(\DOMElement $el): string
    {
        $tag = strtolower($el->nodeName);
        foreach (['src', 'href', 'name', 'id', 'class'] as $name) {
            $value = trim($el->getAttribute($name));
            if ('' === $value) {
                continue;
            }
            if (('src' === $name || 'href' === $name) && 0 === stripos($value, 'data:')) {
                // Inline data URI: summarize instead of dumping the (huge) payload.
                $mime = strtok(substr($value, 5), ';,');
                $value = 'data:'.('' !== (string) $mime ? $mime : 'inline').' (inline)';
            }

            return '<'.$tag.' '.$name.'="'.$value.'">';
        }
        $text = trim(preg_replace('/\s+/', ' ', (string) $el->textContent));

        return '' !== $text ? '<'.$tag.'> '.$text : '<'.$tag.'>';
    }

    /**
     * Append a sample identifier to $bucket while it is below the cap.
     */
    private function sample(array &$bucket, \DOMElement $el): void
    {
        if (count($bucket) < self::MAX_SAMPLES) {
            $bucket[] = $this->describe($el);
        }
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
     * Calibrated indicative score (0-100), NOT a measured Lighthouse score.
     *
     * Weighted average of per-category "health" ratios: each category starts at 1.0
     * and loses health per finding (capped at 0), so a single noisy category cannot
     * sink the whole score, and categories are weighted by importance.
     *
     * @param array<int, array<string, mixed>> $groups
     */
    private function score(array $groups): int
    {
        $weightedSum = 0.0;
        $weightTotal = 0;

        foreach ($groups as $group) {
            $weight = self::SCORE_WEIGHTS[$group['id']] ?? 0;
            if (0 === $weight) {
                continue;
            }
            $health = 1.0;
            foreach ($group['findings'] as $finding) {
                $health -= self::HEALTH_PENALTY[$finding['severity']] ?? 0;
            }
            $weightedSum += $weight * max(0.0, $health);
            $weightTotal += $weight;
        }

        return $weightTotal > 0 ? (int) round($weightedSum / $weightTotal * 100) : 100;
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
