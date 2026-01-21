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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;
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
readonly class ThumbService
{
    /**
     * ThumbService constructor.
     */
    public function __construct(
        private ImageThumbnailInterface $thumbnail,
        private CoreLocatorInterface $coreLocator,
    ) {
    }

    /**
     * To preload resources.
     *
     * @throws NonUniqueResultException
     */
    public function preload(mixed $mediaModel, array $thumbConfiguration = []): array
    {
        $thumbsRender = [];
        $filesystem = new Filesystem();
        $inAdmin = $this->coreLocator->inAdmin();
        $prefixCache = $inAdmin ? 'admin' : 'front';
        $dirnameGenerated = $this->coreLocator->projectDir().'/public/thumbnails/generated/';
        $dirnameGenerated = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirnameGenerated);
        $dirnameGenerated = $dirnameGenerated.$prefixCache.'-'.$mediaModel->media->getWebsite()->getUploadDirname().'.cache.json';
        $jsonData = $filesystem->exists($dirnameGenerated) ? file_get_contents($dirnameGenerated) : null;

        if ($jsonData && $mediaModel->media->getFilename() && preg_match('/'.$mediaModel->media->getFilename().'/', $jsonData)) {
            $files = $this->thumbnail->execute($mediaModel, $thumbConfiguration);
            $thumbs = !empty($files['lazyFile']) ? [$files['lazyFile']] : [];
            $thumbs = !empty($files['files']) ? array_replace($thumbs, $files['files']) : $thumbs;
            $sizesDisplay = !empty($files['sizesDisplay']) ? $files['sizesDisplay'] : $this->thumbnail->getSizes();
            foreach ($thumbs as $key => $thumb) {
                if ('0' == $key || in_array($key, $sizesDisplay) && !in_array($key, $this->thumbnail->getRetinaSizes()) && !str_contains($thumb, '-blur.')) {
                    $thumbsRender[$key] = $thumb;
                    $linkProvider = $this->coreLocator->request()->attributes->get('_links', new GenericLinkProvider());
                    $this->coreLocator->request()->attributes->set('_links', $linkProvider->withLink(
                        (new Link('preload', $thumb))->withAttribute('as', 'image')
                    ));
                }
            }
        }

        return $thumbsRender;
    }

    /**
     * Get Thumb ConfigurationModel.
     */
    public function thumbConfiguration(WebsiteModel $website, string $classname, ?string $action = null, mixed $filter = null, ?string $type = null): array
    {
        $session = new Session();
        $thumbsSession = $session->get('thumbs_actions_'.$website->uploadDirname);
        $thumbs = $thumbsSession ?: [];
        $type = !$type && Block::class === $classname ? $filter : $type;

        if ($type && str_contains($type, '-large')) {
            $filter = 'large';
            $type = str_replace('-large', '', $type);
        }

        if (!$thumbs || $this->coreLocator->request()->get('thumbs')) {
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
            $session->set('thumbs_actions_'.$website->uploadDirname, $thumbs);
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