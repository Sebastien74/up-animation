<?php

declare(strict_types=1);

namespace App\Service\Development\Cms;

use App\Entity\Core\Module;
use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Media\Folder;
use App\Entity\Media\Media;
use App\Entity\Media\MediaRelation;
use App\Entity\Media\MediaRelationIntl;
use App\Entity\Module\Form\Form;
use App\Entity\Module\Gallery\Gallery;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Service\Core\Urlizer;
use Symfony\Component\Filesystem\Filesystem;

/**
 * ImportV4Service.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImportV4Service extends ImportService
{
    /**
     * To set folder name.
     */
    protected function setFolderName(string $name, ?string $slug = null): void
    {
        $this->folderName = $name;
        $this->folderSlug = $slug ? Urlizer::urlize($slug) : Urlizer::urlize($name);
    }

    /**
     * To get locale.
     */
    protected function getLocale(Website $website, array $entityToImport): string
    {
        $locale = $website->getConfiguration()->getLocale();
        if (!empty($entityToImport['website_id']) && !empty($this->websites[$entityToImport['website_id']]['locale'])) {
            $locale = $this->websites[$entityToImport['website_id']]['locale'];
        }

        return $locale;
    }

    /**
     * To set core.
     */
    public function setCore(Website $website, string $classname, string $table, string $sort = 'id', string $order = 'ASC'): void
    {
        $this->classname = $classname;
        $this->setPosition($website, $classname);
        $this->setEntities($table, $sort, $order);
    }

    /**
     * To set entities.
     */
    protected function setEntities(string $table, string $sort = 'id', string $order = 'ASC'): void
    {
        $this->entities = $this->sqlService->findAll($table, $sort, $order);
    }

    /**
     * To get entities.
     */
    public function getEntities(string $table): array
    {
        return $this->entities;
    }

    /**
     * To set default Properties.
     *
     * @throws \Exception
     */
    protected function setProperties(
        mixed $entity,
        Website $website,
        ?string $prefix = null,
        array $entityToImport = [],
        ?string $locale = null,
        int $position = 1
    ): void {
        $name = !empty($entityToImport[$prefix.'_title']) ? $entityToImport[$prefix.'_title']
            : (!empty($entityToImport[$prefix.'_name']) ? $entityToImport[$prefix.'_name'] : null);
        if (method_exists($entity, 'setAdminName') && $name) {
            $entity->setAdminName($name);
        }

        if (method_exists($entity, 'setOldId')) {
            $entity->setOldId($entityToImport['id']);
        }

        $slug = $entityToImport['url_code'] = !empty($entityToImport[$prefix.'_code']) ? $entityToImport[$prefix.'_code'] : Urlizer::urlize($name);
        if (method_exists($entity, 'setSlug') && $slug) {
            $entity->setSlug(trim($slug, '-'));
        }

        if (method_exists($entity, 'setWebsite')) {
            $entity->setWebsite($website);
        }

        $promote = !empty($entityToImport[$prefix.'_is_teasing']) ? $entityToImport[$prefix.'_is_teasing'] : false;
        if (method_exists($entity, 'setPromote')) {
            $entity->setPromote($promote);
        }

        $startDate = !empty($entityToImport[$prefix.'_date_start']) ? $entityToImport[$prefix.'_date_start'] : (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s');
        if (method_exists($entity, 'setPublicationStart')) {
            $entity->setPublicationStart(new \DateTime($startDate));
        }

        $endDate = !empty($entityToImport[$prefix.'_date_end']) ? $entityToImport[$prefix.'_date_end'] : false;
        if (method_exists($entity, 'setPublicationEnd') && $endDate) {
            $entity->setPublicationEnd(new \DateTime($endDate, new \DateTimeZone('Europe/Paris')));
        }

        $active = !empty($entityToImport['online']) ? $entityToImport['online'] : false;
        if (method_exists($entity, 'setActive') && $active) {
            $entity->setActive($active);
        }

        if (method_exists($entity, 'setPosition') && !$entity->getId() && !method_exists($entity, 'getLevel')) {
            $entity->setPosition($position);
        } elseif (method_exists($entity, 'setPosition') && !$entity->getId() && method_exists($entity, 'getLevel')) {
            $level = $entityToImport[$prefix.'_level'] + 1;
            $entity->setPosition($this->getPositionByLevel($website, $this->classname, $level));
            $entity->setLevel($level);
        }

        if (method_exists($entity, 'setCreatedBy')) {
            $entity->setCreatedBy($this->user);
        }

        $createdAt = !empty($entityToImport['created']) ? new \DateTime($entityToImport['created']) : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        if (method_exists($entity, 'setCreatedAt')) {
            $entity->setCreatedAt($createdAt);
        }

        $title = !empty($entityToImport[$prefix.'_title']) ? $entityToImport[$prefix.'_title'] : (!empty($entityToImport[$prefix.'_name']) ? $entityToImport[$prefix.'_name'] : null);
        $intro = !empty($entityToImport[$prefix.'_introduction']) ? $entityToImport[$prefix.'_introduction'] : null;
        $body = !empty($entityToImport[$prefix.'_description']) ? $entityToImport[$prefix.'_description'] : (!empty($entityToImport[$prefix.'_body']) ? $entityToImport[$prefix.'_body'] : null);
        $targetLink = !empty($entityToImport[$prefix.'_url']) ? $entityToImport[$prefix.'_url'] : (!empty($entityToImport[$prefix.'_link']) ? $entityToImport[$prefix.'_link'] : null);
        $targetLabel = !empty($entityToImport[$prefix.'_link_label']) ? $entityToImport[$prefix.'_link_label'] : null;
        $targetBlank = !empty($entityToImport[$prefix.'_link_target']) ? $entityToImport[$prefix.'_link_target'] : false;
        $content = $title || $intro || $body || $targetLink ? ['title' => $title, 'intro' => $intro, 'body' => $body, 'targetLink' => $targetLink, 'targetLabel' => $targetLabel, 'targetBlank' => $targetBlank] : [];
        if (!empty($content)) {
            $this->addIntl($entity, $website, $locale, $content);
        }

        if (!$entity instanceof Layout\Page) {
            $this->addMediaRelations($entity, $website, $locale, $prefix, $entityToImport);
        }
        $this->addUrl($entity, $website, $locale, $prefix, $entityToImport, $content);
        if ($entity instanceof Layout\Page) {
            $this->addLayout($entity, $website, $locale, $prefix, $entityToImport);
        }
    }

    /**
     * To add intl.
     */
    protected function addIntl(mixed $entity, Website $website, string $locale, array $content = []): void
    {
        if (method_exists($entity, 'getIntls')) {
            $title = !empty($content['title']) ? $content['title'] : null;
            $intro = !empty($content['intro']) ? $content['intro'] : null;
            $body = !empty($content['body']) ? $content['body'] : null;
            $body = preg_replace('/<div[^>]*>/', '', $body, 1); /* Remove the first div */
            $body = preg_replace('/<\/div>/', '', $body, -1); /* Remove the last div */
            $video = !empty($content['video']) ? $content['video'] : null;
            $targetLink = !empty($content['targetLink']) ? $content['targetLink'] : null;
            $targetLabel = !empty($content['targetLabel']) ? $content['targetLabel'] : null;
            $targetBlank = $content['targetBlank'] ?? false;

            $intl = new Intl();
            foreach ($entity->getIntls() as $intlEntity) {
                if ($intlEntity->getLocale() === $locale) {
                    $intl = $intlEntity;
                }
            }

            $intl->setTitle($title);
            $intl->setIntroduction($intro);
            $intl->setBody($body);
            $intl->setVideo($video);
            $intl->setTargetLink($targetLink);
            $intl->setTargetLabel($targetLabel);
            $intl->setNewTab($targetBlank);
            $intl->setLocale($locale);

            if (!$intl->getId()) {
                $intl->setLocale($locale);
                $intl->setWebsite($website);
                $entity->addIntl($intl);
            }
        }
    }

    /**
     * To add MediaRelation [].
     *
     * @throws \Exception
     */
    public function addMediaRelations(
        mixed $entity,
        Website $website,
        ?string $locale = null,
        ?string $prefix = null,
        array $entityToImport = [],
        bool $remove = true): void
    {
        if ($remove && method_exists($entity, 'getMediaRelations')) {
            foreach ($entity->getMediaRelations() as $mediaRelation) {
                if ($mediaRelation->getLocale() === $locale) {
                    $entity->removeMediaRelation($mediaRelation);
                    $this->coreLocator->em()->persist($entity);
                }
            }
        } elseif ($remove && method_exists($entity, 'setMediaRelation')) {
            $entity->setMediaRelation(null);
            $this->coreLocator->em()->persist($entity);
        }

        $dirname = $this->coreLocator->projectDir().'/public/uploads/';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);

        $mediaRelationsToImport = (method_exists($entity, 'getMediaRelations') || method_exists($entity, 'getMediaRelation')) && !empty($entityToImport['id'])
            ? $this->sqlService->findBy('fxc_medias_relations', $prefix.'_id', $entityToImport['id'], 'position', 'ASC') : [];
        $position = 1;
        if (!$remove && method_exists($entity, 'getMediaRelations')) {
            foreach ($entity->getMediaRelations() as $mediaRelation) {
                if ($mediaRelation->getLocale() === $locale) {
                    ++$position;
                }
            }
        }
        foreach ($mediaRelationsToImport as $mediaRelationToImport) {
            $mediaToImport = $this->sqlService->find('fxc_medias', 'id', $mediaRelationToImport['media_id']);
            $folderToImport = !empty($mediaToImport['folder_id']) ? $this->sqlService->find('fxc_medias_folders', 'id', $mediaToImport['folder_id']) : [];
            $folderName = !empty($folderToImport['folder_name']) ?: $this->folderName;
            $folderSlug = !empty($folderToImport['folder_name']) ? Urlizer::urlize($folderToImport['folder_name']) : $this->folderSlug;
            $folder = $this->coreLocator->em()->getRepository(Folder::class)->findOneBy(['website' => $website, 'slug' => $folderSlug]);
            $folderPosition = !$folder ? count($this->coreLocator->em()->getRepository(Folder::class)->findBy(['website' => $website, 'level' => 1])) + 1 : $folder->getPosition();
            $folder = $folder ?: new Folder();
            $folder->setAdminName($folderName);
            $folder->setSlug($folderSlug);
            $folder->setPosition($folderPosition);
            $folder->setWebsite($website);
            $this->coreLocator->em()->persist($folder);
            $media = !empty($mediaToImport['media_file']) ? $this->coreLocator->em()->getRepository(Media::class)->findOneBy(['website' => $website, 'filename' => $mediaToImport['media_file']]) : null;
            if (!$media && !empty($mediaToImport['media_file'])) {
                $matches = explode('.', $mediaToImport['media_file']);
                $extension = end($matches);
                $media = new Media();
                $media->setWebsite($website);
                $media->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $media->setCreatedBy($this->user);
                $media->setFolder($folder);
                $media->setFilename(strtolower($mediaToImport['media_file']));
                $media->setName(str_replace('.'.$extension, '', $mediaToImport['media_file']));
                $media->setExtension(strtolower($extension));
            }
            if ($media) {
                $intl = new MediaRelationIntl();
                $intl->setBody($mediaRelationToImport['media_description']);
                $intl->setLocale($locale);
                $intl->setWebsite($website);
                $mediaRelation = new MediaRelation();
                $mediaRelation->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $mediaRelation->setCreatedBy($this->user);
                $mediaRelation->setLocale($locale);
                $mediaRelation->setMedia($media);
                $mediaRelation->setIntl($intl);
                $mediaRelation->setPosition($position);
                $mediaRelation->setPopup($mediaRelationToImport['media_zoom']);
                if (method_exists($entity, 'getMediaRelations')) {
                    $entity->addMediaRelation($mediaRelation);
                } else {
                    $entity->setMediaRelation($mediaRelation);
                }
                ++$position;
            }
            if ($media) {
                $websiteDirname = $dirname.$website->getUploadDirname().DIRECTORY_SEPARATOR.strtolower($mediaToImport['media_file']);
                $filesystem = new Filesystem();
                $importDirname = $dirname.'import'.DIRECTORY_SEPARATOR.$mediaToImport['media_file'];
                $footerFiles = ['filename.png'];
                foreach ($footerFiles as $filename) {
                    if ($filename === $media->getFilename()) {
                        $media->setFilename(str_replace(['png', 'jpg', 'jpeg'], 'png', $media->getFilename()));
                        $media->setExtension('png');
                        $this->coreLocator->em()->persist($media);
                        $importDirname = $dirname.'import-footer'.DIRECTORY_SEPARATOR.$media->getFilename();
                        if ($filesystem->exists($importDirname) && $filesystem->exists($websiteDirname)) {
                            $filesystem->remove($websiteDirname);
                        }
                        $websiteDirname = $dirname.$website->getUploadDirname().DIRECTORY_SEPARATOR.$media->getFilename();
                        $filesystem->copy($importDirname, $websiteDirname);
                    }
                }
                if ($filesystem->exists($importDirname) && !$filesystem->exists($websiteDirname)) {
                    $filesystem->copy($importDirname, $websiteDirname);
                }
            }
        }
    }

    /**
     * To add Url.
     *
     * @throws \Exception
     */
    protected function addUrl(
        mixed $entity,
        Website $website,
        ?string $locale = null,
        ?string $prefix = null,
        array $entityToImport = [],
        array $content = []): void
    {
        if (method_exists($entity, 'addUrl') && !empty($entityToImport['url_code'])) {
            $seoToImport = $this->sqlService->find('fxc_seo', $prefix.'_id', $entityToImport['id']);
            $metaTitle = !empty($seoToImport['meta_title']) ? $seoToImport['meta_title'] : (!empty($content['title']) ? $content['title'] : null);
            $metaOgTitle = !empty($seoToImport['meta_og_title']) ? $seoToImport['meta_og_title'] : (!empty($content['title']) ? $content['title'] : null);
            $metaDescription = !empty($seoToImport['meta_description']) ? $seoToImport['meta_description']
                : (!empty($content['intro']) ? $content['intro'] : (!empty($content['body']) ? substr($content['body'], 0, 500) : null));
            $metaOgDescription = !empty($seoToImport['meta_og_description']) ? $seoToImport['meta_og_description'] : null;
            $metaKeywords = !empty($seoToImport['meta_keywords']) ? $seoToImport['meta_keywords'] : null;
            $metaIndex = $seoToImport['meta_index'] ?? true;
            $metaFollow = $seoToImport['meta_follow'] ?? true;

            $url = new Url();
            foreach ($entity->getUrls() as $urlDb) {
                if ($urlDb->getLocale() === $locale) {
                    $url = $urlDb;
                    break;
                }
            }

            $url->setCode($entityToImport['url_code']);
            $url->setLocale($locale);
            $url->setOnline($entityToImport['online']);
            $url->setAsIndex($metaIndex);
            $url->setHideInSitemap(!$metaIndex);
            $this->setProperties($url, $website);

            $seo = $url->getSeo() ?: new Seo();
            $this->setProperties($seo, $website);
            $seo->setUrl($url);
            $seo->setMetaTitle($metaTitle);
            $seo->setMetaOgTitle($metaOgTitle);
            $seo->setMetaDescription($metaDescription);
            $seo->setMetaOgDescription($metaOgDescription);
            $seo->setKeywords($metaKeywords);

            if (!$seo->getMediaRelation()) {
                $this->addMediaRelations($seo, $website, $locale, 'seo', $seoToImport);
            }

            if (!$seo->getMediaRelation() && method_exists($entity, 'getMediaRelations')) {
                foreach ($entity->getMediaRelations() as $mediaRelationToDuplicate) {
                    if ($mediaRelationToDuplicate->getLocale() === $locale) {
                        if ($mediaRelationToDuplicate->getMedia()) {
                            $intl = new MediaRelationIntl();
                            $intl->setLocale($locale);
                            $intl->setWebsite($website);
                            $intl->setBody($mediaRelationToDuplicate->getIntl()->getBody());
                            $mediaRelation = new MediaRelation();
                            $mediaRelation->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                            $mediaRelation->setCreatedBy($this->user);
                            $mediaRelation->setLocale($locale);
                            $mediaRelation->setIntl($intl);
                            $mediaRelation->setMedia($mediaRelationToDuplicate->getMedia());
                            $seo->setMediaRelation($mediaRelation);
                            break;
                        }
                    }
                }
            }

            if (!$seo->getId()) {
                $url->setSeo($seo);
            }

            if (!$url->getId()) {
                $entity->addUrl($url);
            }
        }
    }

    /**
     * To add Layout.
     *
     * @throws \Exception
     */
    protected function addLayout(
        mixed $entity,
        Website $website,
        ?string $locale = null,
        ?string $prefix = null,
        array $entityToImport = []
    ): void {

        if (method_exists($entity, 'getLayout') && $prefix) {

            $layout = $entity->getLayout();
            if ($layout instanceof Layout\Layout) {
                $this->coreLocator->em()->remove($layout);
            }

            $position = count($this->coreLocator->em()->getRepository(Layout\Layout::class)->findBy(['website' => $website])) + 1;
            $layout = new Layout\Layout();
            $layout->setAdminName($entity->getAdminName());
            $layout->setSlug(Urlizer::urlize($entity->getAdminName()));
            $layout->setPosition($position);
            $layout->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $layout->setCreatedBy($this->user);
            $layout->setWebsite($website);
            $this->coreLocator->em()->persist($layout);

            $zonesToImport = $this->sqlService->findBy('fxc_zones', $prefix.'_id', $entityToImport['id'], 'position', 'ASC');
            $zone = new Layout\Zone();
            $zone->setPosition(1);
            $zone->setFullSize(true);
            $layout->addZone($zone);
            $col = new Layout\Col();
            $col->setPosition(1);
            $col->setSize(12);
            $zone->addCol($col);
            $this->setBlockType($website, $col, 'title-header', ['block_name' => $entityToImport[$prefix.'_name']], $locale, 1);
            foreach ($zonesToImport as $zoneToImport) {
                $zone = new Layout\Zone();
                $zone->setPosition($zoneToImport['position'] + 1);
                if ($zoneToImport['zone_hide_mobile']) {
                    $zone->setHideMobile(true);
                }
                $layout->addZone($zone);
                $colsToImport = $this->sqlService->findBy('fxc_zones_cols', 'zone_id', $zoneToImport['id'], 'position', 'ASC');
                foreach ($colsToImport as $colToImport) {
                    $col = new Layout\Col();
                    $col->setPosition($colToImport['position']);
                    $col->setSize($colToImport['col_type']);
                    $zone->addCol($col);
                    $blocksToImport = $this->sqlService->findBy('fxc_blocks', 'col_id', $colToImport['id'], 'position', 'ASC');
                    $position = 1;
                    foreach ($blocksToImport as $blockToImport) {
                        $blockTypeToImport = $this->sqlService->find('fxc_block_types', 'id', $blockToImport['blocktype_id']);
                        $position = $this->setBlockType($website, $col, $blockTypeToImport['type_code'], $blockToImport, $locale, $position);
                    }
                }
            }

            $this->layoutManager->setGridZone($layout);

            $entity->setLayout($layout);
        }
    }

    /**
     * To get BlockType.
     *
     * @throws \Exception
     */
    private function setBlockType(Website $website, Layout\Col $col, string $slug, array $blockToImport, string $locale, int $position): int
    {
        if ('text' === $slug) {
            if (!empty($blockToImport['block_title'])) {
                $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => 'title']);
                $block = new Layout\Block();
                $block->setBlockType($blockType);
                $this->setProperties($block, $website, 'block', $blockToImport, $locale);
                $block->setPosition($position);
                $col->addBlock($block);
                foreach ($block->getIntls() as $intl) {
                    $intl->setBody(null);
                    $intl->setIntroduction(null);
                }
                ++$position;
            }

            if (!empty($blockToImport['block_introduction']) || !empty($blockToImport['block_body'])) {
                $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => 'text']);
                $block = new Layout\Block();
                $block->setBlockType($blockType);
                $this->setProperties($block, $website, 'block', $blockToImport, $locale);
                $block->setPosition($position);
                $col->addBlock($block);
                foreach ($block->getIntls() as $intl) {
                    $intl->setTitle(null);
                    $intl->setTargetPage(null);
                    $intl->setTargetLink(null);
                    $intl->setTargetLabel(null);
                }
                ++$position;
            }

            if (!empty($blockToImport['block_link'])) {
                $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => 'link']);
                $block = new Layout\Block();
                $block->setBlockType($blockType);
                $this->setProperties($block, $website, 'block', $blockToImport, $locale);
                $block->setPosition($position);
                $col->addBlock($block);
                foreach ($block->getIntls() as $intl) {
                    $intl->setTitle(null);
                    $intl->setBody(null);
                    $intl->setIntroduction(null);
                }
                ++$position;
            }
        } elseif ('title-header' === $slug) {
            $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => $slug]);
            $block = new Layout\Block();
            $block->setBlockType($blockType);
            $this->setProperties($block, $website, 'block', $blockToImport, $locale);
            $block->setPosition($position);
            $block->setPaddingLeft('ps-0');
            $block->setPaddingRight('pe-0');
            $col->addBlock($block);
            ++$position;
        } elseif ('action' !== $slug) {
            $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => $slug]);
            if ($blockType instanceof Layout\BlockType) {
                $block = new Layout\Block();
                $block->setBlockType($blockType);
                $this->setProperties($block, $website, 'block', $blockToImport, $locale);
                $block->setPosition($position);
                $col->addBlock($block);
                ++$position;
            }
        } elseif (!empty($blockToImport['action_id'])) {
            $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => 'core-action']);
            $actionToImport = $this->sqlService->find('fxc_actions', 'id', $blockToImport['action_id']);
            $excluded = ['news', 'view', 'calendar', 'effectif', 'billetterie', 'billetterieWithSubscription', 'weezeventSubscription', 'staff', 'listing', 'thanks'];
            $actionCode = !empty($actionToImport['action_code']) && 'summary' !== $actionToImport['action_code'] ? $actionToImport['action_code'] : null;
            $action = null;

            if ('galleries' === $actionCode) {
                $action = $this->coreLocator->em()->getRepository(Layout\Action::class)->findOneBy(['entity' => Gallery::class, 'action' => 'view']);
                $this->addWebsiteModule($website, 'ROLE_GALLERY');
            } elseif ('contact' === $actionCode || 'form' === $actionCode) {
                $action = $this->coreLocator->em()->getRepository(Layout\Action::class)->findOneBy(['entity' => Form::class, 'action' => 'view']);
                $this->addWebsiteModule($website, 'ROLE_FORM');
            } elseif ($actionCode && !in_array($actionCode, $excluded)) {
                dd($actionCode);
            } elseif ('view' === $actionCode) {
                //                dd($actionToImport);
            }

            if ($action instanceof Layout\Action && $blockType instanceof Layout\BlockType) {
                $block = new Layout\Block();
                $block->setBlockType($blockType);
                $block->setAction($action);
                $this->setProperties($block, $website, 'block', $blockToImport, $locale);
                $block->setPosition($position);
                $col->addBlock($block);
                ++$position;
            }
        }

        return $position;
    }

    /**
     * To add WebsiteModel Module.
     */
    private function addWebsiteModule(Website $website, string $role): void
    {
        $module = $this->coreLocator->em()->getRepository(Module::class)->findOneBy(['role' => $role]);
        $configuration = $website->getConfiguration();
        $inCollection = false;
        foreach ($configuration->getModules() as $configModule) {
            if ($role === $configModule->getRole()) {
                $inCollection = true;
                break;
            }
        }
        if (!$inCollection) {
            $configuration->addModule($module);
            $this->coreLocator->em()->persist($configuration);
        }
    }

    /**
     * To get position.
     */
    protected function getPositionByLevel(Website $website, string $classname, int $level): int
    {
        $referEntity = new $classname();
        $repository = $this->coreLocator->em()->getRepository($classname);
        if (method_exists($referEntity, 'getWebsite')) {
            $position = count($repository->findBy(['website' => $website, 'level' => $level])) + 1;
        } else {
            $position = count($repository->findBy(['level' => $level])) + 1;
        }

        return $position;
    }
}
