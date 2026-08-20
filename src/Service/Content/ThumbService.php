<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Layout\Block;
use App\Entity\Layout\BlockType;
use App\Entity\Media\ThumbAction;
use App\Entity\Media\ThumbConfiguration;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * ThumbService.
 *
 * Manage image crop
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ThumbService::class, 'key' => 'thumb_service'],
])]
class ThumbService
{
    private const string PRELOAD_SIZES = '100vw';
    private const array MIME_BY_EXTENSION = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];

    private static array $preloadJsonRaw = [];
    private array $thumbConfigurationCache = [];
    private array $thumbsActionsCache = [];

    /**
     * ThumbService constructor.
     */
    public function __construct(
        private readonly ImageThumbnailInterface $thumbnail,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
    }

    /**
     * To preload resources.
     *
     * @throws NonUniqueResultException
     */
    public function preload(mixed $mediaModel, array $thumbConfiguration = []): array
    {
        return $this->doPreload($mediaModel, $thumbConfiguration);
    }

    private function doPreload(mixed $mediaModel, array $thumbConfiguration = []): array
    {
        $thumbsRender = [];
        $inAdmin = $this->coreLocator->inAdmin();
        $prefixCache = $inAdmin ? 'admin' : 'front';
        $dirnameGenerated = $this->coreLocator->projectDir().'/public/thumbnails/generated/';
        $dirnameGenerated = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirnameGenerated);
        $dirnameGenerated = $dirnameGenerated.$prefixCache.'-'.$mediaModel->media->getWebsite()->getUploadDirname().'.cache.json';

        if (!array_key_exists($dirnameGenerated, self::$preloadJsonRaw)) {
            self::$preloadJsonRaw[$dirnameGenerated] = is_file($dirnameGenerated) ? file_get_contents($dirnameGenerated) : null;
        }
        $jsonData = self::$preloadJsonRaw[$dirnameGenerated];
        $originalName = $mediaModel->media->getOriginalName();

        if ($jsonData && $originalName && str_contains($jsonData, $originalName)) {
            $files = $this->thumbnail->execute($mediaModel, $thumbConfiguration);
            $thumbs = !empty($files['files']) ? $files['files'] : [];
            ksort($thumbs);

            // Same candidate set as the rendered <picture> (all sizes, deduplicated): a narrower set makes the browser preload a variant the picture never uses.
            $srcset = $seen = [];
            foreach ($thumbs as $key => $thumb) {
                if ((int) $key <= 0 || !$thumb || str_contains($thumb, '-blur.') || in_array($thumb, $seen, true)) {
                    continue;
                }
                $seen[] = $thumb;
                $srcset[$key] = str_replace('/public/', '/', $thumb).' '.(int) $key.'w';
            }

            if ($srcset) {
                $href = (string) strtok((string) reset($srcset), ' ');
                $extension = strtolower(pathinfo(parse_url($href, PHP_URL_PATH) ?: $href, PATHINFO_EXTENSION));
                $mime = self::MIME_BY_EXTENSION[$extension] ?? null;
                $thumbsRender = [
                    'href' => $href,
                    'srcset' => implode(', ', $srcset),
                    'sizes' => self::PRELOAD_SIZES,
                    'type' => $mime,
                ];

                $link = (new Link('preload', $href))
                    ->withAttribute('as', 'image')
                    ->withAttribute('fetchpriority', 'high')
                    ->withAttribute('imagesrcset', $thumbsRender['srcset'])
                    ->withAttribute('imagesizes', self::PRELOAD_SIZES);
                if ($mime) {
                    $link = $link->withAttribute('type', $mime);
                }
                $linkProvider = $this->coreLocator->request()->attributes->get('_links', new GenericLinkProvider());
                $this->coreLocator->request()->attributes->set('_links', $linkProvider->withLink($link));
            }
        }

        return $thumbsRender;
    }

    /**
     * Get Thumb ConfigurationModel.
     */
    public function thumbConfiguration(WebsiteModel $website, string $classname, ?string $action = null, mixed $filter = null, ?string $type = null): array
    {
        return $this->doThumbConfiguration($website, $classname, $action, $filter, $type);
    }

    private function doThumbConfiguration(WebsiteModel $website, string $classname, ?string $action = null, mixed $filter = null, ?string $type = null): array
    {
        $type = !$type && Block::class === $classname ? $filter : $type;

        if ($type && str_contains($type, '-large')) {
            $filter = 'large';
            $type = str_replace('-large', '', $type);
        }

        $bustCache = (bool) $this->coreLocator->requestGet('thumbs');
        $cacheKey = null;
        if (!$bustCache && ($filter === null || is_scalar($filter))) {
            $cacheKey = $website->uploadDirname.'|'.$classname.'|'.($action ?? '')
                .'|'.(is_bool($filter) ? (int) $filter : ($filter ?? ''))
                .'|'.($type ?? '');
            if (isset($this->thumbConfigurationCache[$cacheKey])) {
                return $this->thumbConfigurationCache[$cacheKey];
            }
        }

        // Per-request memo of the website's thumb actions (was cached in the session, which
        // opened a session on every page with images and blocked shared-cache caching).
        $memoKey = $website->uploadDirname;
        $thumbs = $bustCache ? [] : ($this->thumbsActionsCache[$memoKey] ?? []);

        if (!$thumbs || $bustCache) {
            $thumbs = [];
            $thumbsActions = $this->coreLocator->em()->getRepository(ThumbAction::class)->findByWebsite($website);
            foreach ($thumbsActions as $thumbAction) {
                /** @var ThumbAction $thumbAction */
                $blockType = $thumbAction->getBlockType();
                $thumbConfig = [
                    'screen' => $thumbAction->getConfiguration()->getScreen(),
                    'action' => $thumbAction->getAction(),
                    'actionFilter' => $thumbAction->getActionFilter(),
                    'blockType' => $blockType,
                    'blockTypeSlug' => $blockType instanceof BlockType ? $blockType->getSlug() : null,
                    'entity' => $thumbAction,
                ];
                if (empty($thumbs[$thumbAction->getNamespace()]) || !$thumbAction->getActionFilter()) {
                    $thumbs[$thumbAction->getNamespace()][] = $thumbConfig;
                } else {
                    array_unshift($thumbs[$thumbAction->getNamespace()], $thumbConfig);
                }
            }
            $this->thumbsActionsCache[$memoKey] = $thumbs;
        }

        $configurations = [];
        foreach (['desktop', 'tablet', 'mobile'] as $screen) {
            $configurations[$screen] = $this->getThumbConfiguration($screen, $thumbs, $classname, $action, $filter, $type);
            if (!$configurations[$screen] && 'large' !== $filter) {
                $configurations[$screen] = $this->getThumbConfiguration($screen, $thumbs, $classname, $action, null, $type);
            }
            if (!$configurations[$screen]) {
                unset($configurations[$screen]);
            }
        }

        if ($cacheKey !== null) {
            $this->thumbConfigurationCache[$cacheKey] = $configurations;
        }

        return $configurations;
    }

    /**
     * Get Thumb ConfigurationModel.
     */
    public function getThumbConfiguration(string $screen, array $thumbs, string $classname, ?string $action = null, mixed $filter = null, ?string $type = null): ?ThumbConfiguration
    {
        $thumbsActions = !empty($thumbs[$classname]) ? $thumbs[$classname] : [];
        foreach ($thumbsActions as $thumbAction) {
            $thumbAction['screen'] = !empty($thumbAction['screen']) ? $thumbAction['screen'] : 'desktop';
            if (!empty($thumbAction['screen']) && $screen === $thumbAction['screen']) {
                if ('view' === $action && is_string($filter) && str_contains($filter, 'associated') && $thumbAction['actionFilter'] && str_contains($thumbAction['actionFilter'], 'associated')) {
                    return $thumbAction['entity']->getConfiguration();
                } elseif (!$action && !$thumbAction['action'] && !$filter && !$thumbAction['actionFilter'] && !$type && !$thumbAction['blockType']) {
                    return $thumbAction['entity']->getConfiguration();
                } elseif (Block::class === $classname && $type && !empty($thumbAction['blockTypeSlug']) && $thumbAction['blockTypeSlug'] === $type && $thumbAction['actionFilter'] && $filter == $thumbAction['actionFilter']) {
                    return $thumbAction['entity']->getConfiguration();
                } elseif ($type && $thumbAction['blockTypeSlug'] === $type && !empty($thumbAction['blockTypeSlug']) && !$thumbAction['actionFilter'] && 'large' !== $filter) {
                    return $thumbAction['entity']->getConfiguration();
                } elseif ((is_object($filter) && $thumbAction['action'] === $action) && (method_exists($filter, 'getSlug') && $filter->getSlug() == $thumbAction['actionFilter']
                        || method_exists($filter, 'getId') && $filter->getId() == $thumbAction['actionFilter'])) {
                    return $thumbAction['entity']->getConfiguration();
                } elseif (Block::class === $classname && $thumbAction['blockType'] instanceof BlockType && $type === $thumbAction['blockTypeSlug'] && !$thumbAction['actionFilter'] && 'large' !== $filter) {
                    return $thumbAction['entity']->getConfiguration();
                } elseif ($thumbAction['action'] === $action && !empty($thumbAction['actionFilter']) && $thumbAction['actionFilter'] === $filter) {
                    return $thumbAction['entity']->getConfiguration();
                } elseif ($thumbAction['action'] === $action && !$filter && !$thumbAction['actionFilter'] && !$type && empty($thumbAction['blockTypeSlug'])) {
                    return $thumbAction['entity']->getConfiguration();
                } elseif ($thumbAction['action'] === $action && $filter && $filter == $thumbAction['actionFilter']) {
                    return $thumbAction['entity']->getConfiguration();
                }
            }
        }

        return null;
    }

    /**
     * Get Thumb by filter.
     */
    public function thumbConfigurationByFilter(WebsiteModel $website, string $classname, $filter = null): array
    {
        /** @var ThumbAction $thumbAction */
        $thumbAction = $this->coreLocator->em()->getRepository(ThumbAction::class)->findByNamespaceAndFilter($website, $classname, $filter);

        return $thumbAction instanceof ThumbAction ? [$thumbAction->getConfiguration()] : [];
    }
}