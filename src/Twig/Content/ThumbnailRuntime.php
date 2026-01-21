<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Media;
use App\Model\IntlModel;
use App\Model\MediaModel;
use App\Service\Content\ImageThumbnailInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * ThumbnailRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ThumbnailRuntime implements RuntimeExtensionInterface
{
    private ?Request $request = null;
    private string $projectDirname;
    private array $options = [];
    private array $arguments = [];

    /**
     * ThumbnailRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly Environment $templating,
        private readonly IconRuntime $iconRuntime,
        private readonly ImageThumbnailInterface $imageThumbnail,
    ) {
        $this->projectDirname = $this->coreLocator->projectDir();
    }

    /**
     * To set request.
     */
    private function setRequest(): void
    {
        if (!$this->request instanceof Request) {
            $this->request = $this->coreLocator->request();
        }
    }

    /**
     * Get file icon.
     */
    public function fileIcon(string $type, ?string $extension = null, ?string $class = null): ?string
    {
        if (in_array($extension, $this->imageThumbnail->getAllowedExtensions())) {
            return null;
        }

        $class = $class ? ' '.$class : $class;
        $icons = [];

        $icons['admin'] = [
            'pdf' => 'fas file-pdf',
            'docx' => 'fas file-word',
            'xlsx' => 'fas file-excel',
            'txt' => 'fas file-alt',
            'mp4' => 'fas video',
            'mp3' => 'fas volume-up',
        ];

        $icons['front'] = [
            'pdf' => '<i class="icon-file-pdf'.$class.'"></i>',
            'docx' => '<i class="icon-file-word'.$class.'"></i>',
            'xlsx' => '<i class="icon-file-excel'.$class.'"></i>',
            'txt' => '<i class="icon-file-alt'.$class.'"></i>',
            'mp4' => '<i class="icon-video'.$class.'"></i>',
            'mp3' => '<i class="icon-volume-up'.$class.'"></i>',
        ];

        return !empty($icons[$type][$extension]) ? $icons[$type][$extension] : ('front' === $type ? '<i class="icon-file-alt'.$class.'"></i>' : 'fas file-alt');
    }

    /**
     * Get file loader.
     *
     * @throws LoaderError|RuntimeError|SyntaxError|NonUniqueResultException|MappingException
     */
    public function file(mixed $src = null, array $thumbs = [], array $options = []): mixed
    {
        if (!isset($options['beforeRender'])) {
            if ((isset($options['path']) && true === (bool) $options['path'])
                || (isset($options['file']) && true === (bool) $options['file'])
                || (isset($options['asMedia']) && true === (bool) $options['asMedia'])) {
                return $this->thumb($src, $thumbs, $options);
            } elseif (($src && is_string($src))
                || (isset($options['only_html']) && true === (bool) $options['only_html'])
                || (isset($options['style']) && true === (bool) $options['style'])) {
                $options['src'] = $src;
                $options['thumb'] = $options['thumb'] ?? [];
                $options['thumbs'] = $thumbs;
                return $this->fileRender($options);
            }
        }

        $placeholder = $options['placeholder'] ?? false;
        $src = $src instanceof MediaModel ? $src->mediaRelation : $src;
        $media = $options['mediaModel'] = is_object($src) ? MediaModel::fromEntity($src, $this->coreLocator, false) : null;
        $options['options'] = $options;
        $options['radius'] = $media instanceof MediaModel ? $media->radius : false;
        $options['thumbConfiguration'] = $thumbs;
        $options['lazyLoad'] = (isset($options['lazyLoad']) && $options['lazyLoad']) || !isset($options['lazyLoad']);
        $thumbConfigurationJson = array_map(function ($thumb) {
            return $thumb->getId();
        }, $thumbs);
        $options['options']['thumbConfigurationJson'] = urlencode(json_encode($thumbConfigurationJson));
        $media = $this->setPlaceholder($media, $options);
        $asPlaceholder = $this->asPlaceholder($media);

        if ($media instanceof MediaModel && $media->media && $media->media->getFilename() && !$asPlaceholder) {
            $filesystem = new Filesystem();
            $thumb = end($thumbs);
            $inAdmin = $this->coreLocator->inAdmin();
            $prefixCache = $inAdmin ? 'admin' : 'front';
            $width = !empty($options['width']) ? $options['width'] : (!empty($options['maxWidth']) ? $options['maxWidth'] : ($thumb instanceof Media\ThumbConfiguration ? $thumb->getWidth() : ''));
            $height = !empty($options['height']) ? $options['height'] : (!empty($options['maxHeight']) ? $options['maxHeight'] : ($thumb instanceof Media\ThumbConfiguration ? $thumb->getHeight() : ''));
            if (empty($width) || empty($height)) {
                $dirname = $this->coreLocator->website()->uploadDirname;
                $dirname = $this->coreLocator->projectDir().'/public/uploads/'.$dirname.'/'.$media->media->getFilename();
                $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
                if ($filesystem->exists($dirname)) {
                    list($width, $height) = getimagesize($dirname);
                }
            }
            $filter = !empty($options['filter']) ? $options['filter'] : '';
            $filename = $filter.$width.$height.$media->id.$media->media->getFilename();
            $allowedExtension = !in_array($media->media->getExtension(), $this->imageThumbnail->getExceptionsExtensions());
            $dirnameGenerated = $this->coreLocator->projectDir().'/public/thumbnails/generated/';
            $dirnameGenerated = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirnameGenerated);
            $dirnameGenerated = $dirnameGenerated.$prefixCache.'-'.$media->media->getWebsite()->getUploadDirname().'.cache.json';
            $jsonData = $filesystem->exists($dirnameGenerated) ? (array) json_decode(file_get_contents($dirnameGenerated)) : [];
            $generateThumbs = !isset($jsonData[$filename]);
            $options['loader'] = $generateThumbs;
            $options['loaderFilename'] = $filename;
            $options['lazyFiles'] = $options['onlyLazy'] = !$generateThumbs;
            $thumbnails = !isset($options['beforeRender']) && ($generateThumbs || $options['lazyFiles']) ? $this->imageThumbnail->execute($media, $thumbs, $options) : [];
            $options['loaderSrc'] = $options['dataSource'] = !empty($thumbnails['dataSource']) ? $thumbnails['dataSource'] : $src;
            $options['loaderSvgSrc'] = !empty($thumbnails['lazyFileSvg']) ? $thumbnails['lazyFileSvg'] : (is_string($src) ? $src : 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
            $options['entity'] = $src;
            $options['thumbs'] = $thumbnails['thumbs'] ?? null;
            $options['alt'] = $thumbnails['infos']['alt'] ?? null;
            $options['title'] = $thumbnails['infos']['title'] ?? null;
            $options['width'] = $thumbnails['infos']['width'] ?? null;
            $options['height'] = $thumbnails['infos']['height'] ?? null;
            $options['extension'] = $thumbnails['infos']['extension'] ?? null;
            $options['lazyFiles'] = $thumbnails['lazyFiles'] ?? [];
            $options['media'] = $media;
            $options['generateThumbs'] = $generateThumbs && $allowedExtension;
            $options['display'] = !isset($options['path']) || !$options['path'];
            $options['spinnerColor'] = $inAdmin ? 'white' : 'primary';
            $options['inAdmin'] = $inAdmin;
            $options['class'] = $options['class'] ?? null;
        } elseif ($media instanceof MediaModel && !$placeholder) {
            return false;
        } else {
            $src = is_array($src) ? $src : [];
            $src['forceThumb'] = true;
            $src['width'] = $options['width'] ?? null;
            $src['height'] = $options['height'] ?? null;
            $src['thumbConfiguration'] = $options['thumbConfiguration'] ?? null;
            $src['screensSizes'] = $options['screensSizes'] ?? [];
            $src['placeholder'] = $options['placeholder'] ?? null;
            $src['class'] = $options['class'] ?? null;
            echo $this->fileRender($src);
            return false;
        }

        $options['radiusClass'] = '';
        if (!empty($options['class'])) {
            $classes = explode(' ', $options['class']);
            foreach ($classes as $class) {
                if (str_contains($class, 'radius')) {
                    $options['radiusClass'] .= ' ' . $class;
                }
            }
            $options['radiusClass'] = trim($options['radiusClass']);
        }

        if (isset($options['beforeRender'])) {
            $options['generateThumbs'] = false;
            $options['onlyLazy'] = true;
            $options['display'] = true;
            $options['onlyHx'] = true;
            return [
                'status' => $generateThumbs ? 'in-progress' : 'generated',
                'path' => !$generateThumbs ? $this->thumb($src, $thumbs, $options) : null,
                'html' => $generateThumbs ? $this->templating->render('core/image-loader.html.twig', $options) : null,
            ];
        } else {
            echo $this->templating->render('core/image-loader.html.twig', $options);
        }

        return false;
    }

    /**
     * Get thumbnail.
     *
     * @throws LoaderError|RuntimeError|SyntaxError|NonUniqueResultException|MappingException
     */
    public function thumb(mixed $media = null, array $thumbs = [], array $options = [])
    {
        $this->setRequest();
        $mediaModel = $media instanceof MediaModel ? $media : (is_object($media) ? MediaModel::fromEntity($media, $this->coreLocator) : null);
        $media = $mediaModel instanceof MediaModel ? $mediaModel->media : null;

        if (!$mediaModel && !isset($options['placeholder']) || $mediaModel instanceof MediaModel && $mediaModel->media && !$mediaModel->media->getFilename() && !isset($options['placeholder'])) {
            return false;
        }

        $this->arguments['website'] = $this->coreLocator->website()->entity;
        $mediaModel = $this->setPlaceholder($mediaModel, $options);
        $media = $mediaModel ? $mediaModel->media : $media;

        if ($mediaModel && !$mediaModel->mediaRelation) {
            $mediaRelation = new Media\MediaRelation();
            $mediaRelation->setLocale($this->request->getLocale());
            $mediaRelation->setMedia($media);
            $mediaModel = MediaModel::fromEntity($mediaRelation, $this->coreLocator);
        }

        if (!$media && !isset($options['placeholder'])) {
            if (!empty($options['style']) && !empty($options['entity'])) {
                return $this->getAttributeStyle($mediaModel, $thumbs, $options);
            }

            return false;
        }

        $uri = $this->request instanceof Request ? $this->request->getUri() : null;
        $allImagesExtensions = array_merge($this->imageThumbnail->getAllowedExtensions(), $this->imageThumbnail->getExceptionsExtensions());

        $this->arguments = [];
        $this->arguments['inPreview'] = preg_match('/\/preview\//', $uri);
        $this->arguments['inAdmin'] = (preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $uri) && !$this->arguments['inPreview']) || ($options['inAdmin'] ?? false);
        $this->arguments['media'] = $mediaModel;
        $this->arguments['mediaRelation'] = $options['mediaRelation'] = $mediaModel instanceof MediaModel ? $mediaModel->mediaRelation : null;
        $this->arguments['popupGallery'] = $options['popupGallery'] = !empty($options['popupGallery']) ? $options['popupGallery'] : false;
        $this->arguments['parentEntity'] = !empty($options['parentEntity']) ? $options['parentEntity'] : null;
        $this->arguments['block'] = !empty($options['block']) ? $options['block'] : $this->arguments['parentEntity'];
        $this->arguments['lazyLoad'] = $options['lazyLoad'] ?? true;
        $this->arguments['targetLink'] = !empty($options['targetLink']) ? $options['targetLink'] : null;
        $this->arguments['targetBlank'] = $options['targetBlank'] ?? false;
        $this->arguments['fullPopup'] = $options['fullPopup'] = $options['fullPopup'] ?? true;
        $this->arguments['displayPage'] = $options['displayPage'] ?? true;
        $this->arguments['priority'] = $options['priority'] ?? null;
        $this->arguments['disableLink'] = $options['disableLink'] ?? false;
        $this->arguments['targetPageIntl'] = isset($options['displayPage']) ? $options['targetPageIntl'] : null;
        $this->arguments['haveArrow'] = $options['haveArrow'] ?? null;
        $this->arguments['titleForce'] = $options['titleForce'] ?? 3;
        $this->arguments['decor'] = $options['decor'] ?? false;
        $this->arguments['popup'] = $mediaModel instanceof MediaModel ? $mediaModel->popup : false;
        $this->arguments['downloadable'] = $mediaModel instanceof MediaModel ? $mediaModel->downloadable : false;
        $this->arguments['website'] = $options['website'] = $website = $this->coreLocator->website()->entity;

        if (isset($options['path']) && $options['path']) {
            $media = !$media && isset($options['placeholder']) ? $this->setPlaceholder($media, $options)->media : $media;
            if (!$media->getWebsite()) {
                $media->setWebsite($website);
            }
            $thumbnails['infos']['path'] = preg_match('/images\/placeholder/', $media->getFilename()) || preg_match('/medias\/placeholder/', $media->getFilename())
                ? $media->getFilename() : 'uploads/'.$media->getWebsite()->getUploadDirname().'/'.$media->getFilename();
            $thumbnail = !empty($thumbnails['infos']['path']) ? $thumbnails['infos']['path'] : null;
            $isImage = $media->getExtension() && in_array($media->getExtension(), $allImagesExtensions);
            if ($isImage && !$this->arguments['inAdmin']) {
                $thumbnailsByService = $this->imageThumbnail->execute($mediaModel, $thumbs, $options);
                if (isset($options['strictSize']) && $options['strictSize'] && isset($options['filter']) && $options['filter']) {
                    return $thumbnailsByService;
                }
                if (!empty($thumbnailsByService['files']) && !empty(end($thumbnailsByService['files'])) && !preg_match('/cache\/resolve/', end($thumbnailsByService['files']))) {
                    $thumbnail = end($thumbnailsByService['files']);
                }
            }

            return $thumbnail;
        }

        if (!empty($options['style'])) {
            return $this->getAttributeStyle($mediaModel, $thumbs, $options);
        }

        if (isset($options['only_html']) && $options['only_html']) {
            $options['src'] = !empty($options['src']) ? $options['src'] : '/uploads/'.$website->getUploadDirname().'/'.$media->getFilename();
            $htmlDirname = preg_match('/include\/svg/', $options['src']) ? $this->projectDirname.'/templates/'.ltrim($options['src'], '/')
                : $this->projectDirname.'/public/'.$options['src'];
            $htmlDirname = str_replace($this->request->getSchemeAndHttpHost(), '', $htmlDirname);
            $htmlDirname = str_replace(['/', '\\', '\\\\', '//'], DIRECTORY_SEPARATOR, $htmlDirname);
            $filesystem = new Filesystem();
            if (preg_match('/.svg/', $options['src']) && $filesystem->exists($htmlDirname)) {
                return $this->iconRuntime->icon($htmlDirname, null, null, null, $options);
            }
        }

        $this->arguments['btnLink'] = isset($options['btnLink']) && $options['btnLink'];
        $this->options = array_merge($options, $this->arguments);

        if (!empty($this->options['execute'])) {
            $generator = $this->options['generator'] ?? false;
            $this->arguments['thumbnails'] = $this->imageThumbnail->execute($mediaModel, $thumbs, $options, $generator);
        }

        $thumbnails = $this->arguments['thumbnails'] = empty($this->arguments['thumbnails']) ? $this->imageThumbnail->execute($mediaModel, $thumbs, $options) : $this->arguments['thumbnails'];
        $extension = $this->arguments['extension'] = !empty($thumbnails['extension']) ? $thumbnails['extension'] : $media->getExtension();
        $isImage = $extension && in_array($extension, $allImagesExtensions);

        $this->inBox($website, $extension, $mediaModel);

        if (!empty($this->options['only_arguments'])) {
            return $this->arguments;
        } elseif (!empty($this->options['config_path'])) {
            return $this->arguments;
        } elseif ($isImage) {
            echo $this->templating->render('core/image-config.html.twig', $this->arguments);
        } elseif (!$this->arguments['inAdmin'] && 'mp3' === $extension) {
            $this->arguments['file'] = $this->arguments['thumbnails']['dataSource'];
            echo $this->templating->render('front/'.$this->arguments['website']->getConfiguration()->getTemplate().'/blocks/file/audio.html.twig', $this->arguments);
        } elseif (!$this->arguments['inAdmin'] && 'mp4' === $extension) {
            echo $this->templating->render('core/video.html.twig', $this->arguments);
        } elseif (!$this->arguments['inAdmin'] || $this->arguments['inPreview']) {
            $this->arguments['websiteTemplate'] = $options['website']->getConfiguration()->getTemplate();
            echo $this->templating->render('front/'.$this->arguments['websiteTemplate'].'/blocks/file/default.html.twig', $this->arguments);
        }

        return null;
    }

    /**
     * Get img by Imagine filter.
     *
     * @throws LoaderError|RuntimeError|SyntaxError|NonUniqueResultException|MappingException
     */
    public function imgFilter(MediaModel $mediaModel, string $filter, bool $strictSize = false): string
    {
        $path = $this->thumb($mediaModel, [], [
            'filter' => $filter,
            'strictSize' => $strictSize,
            'path' => $strictSize,
            'forceThumb' => true,
            'noCache' => true,
        ]);

        return !is_bool($path) ? $path : '';
    }

    /**
     * To generate image render with options.
     *
     * @throws LoaderError
     * @throws NonUniqueResultException
     * @throws RuntimeError
     * @throws SyntaxError|MappingException
     */
    private function fileRender(array $options = []): array|string|null
    {
        $this->setRequest();
        $isSlash = !empty($options['src']) && is_string($options['src']) ? str_starts_with($options['src'], '/') : null;
        $options['src'] = $isSlash ? $options['src'] : (!empty($options['src']) && $options['src'] instanceof MediaModel ? $options['src']->path : (!empty($options['src']) && is_string($options['src']) ? '/'.$options['src'] : null));
        $filesystem = new Filesystem();
        $fileDirname = $this->projectDirname.'/public'.$options['src'];
        $fileDirname = str_replace([$this->request->getSchemeAndHttpHost()], '', $fileDirname);
        $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);
        $fileDirname = $filesystem->exists($fileDirname) && !is_dir($fileDirname) ? $fileDirname : $this->projectDirname.'/public/medias/placeholder.jpg';

        $media = null;
        $thumbs = !empty($options['thumbs']) ? $options['thumbs'] : (!empty($options['thumbConfiguration']) ? $options['thumbConfiguration'] : []);
        if ((isset($options['style']) && !str_contains('placeholder', $fileDirname)) || !isset($options['style'])) {
            $file = new File($fileDirname);
            $media = new Media\Media();
            $media->setFilename($file->getFilename());
            $media->setExtension($file->getExtension());
            $media->setScreen('desktop');
            $media->setWebsite($this->coreLocator->website()->entity);
            $mediaRelation = new Media\MediaRelation();
            $mediaRelation->setLocale($this->request->getLocale());
            $mediaRelation->setMedia($media);
            $media = MediaModel::fromEntity($mediaRelation, $this->coreLocator);
            $options['file'] = $file;
            $options['maxWidth'] = !empty($options['maxWidth']) ? $options['maxWidth'] : (!empty($options['width']) ? $options['width'] : null);
            $options['maxHeight'] = !empty($options['maxHeight']) ? $options['maxHeight'] : (!empty($options['height']) ? $options['height'] : null);
        }

        return $this->thumb($media, $thumbs, $options);
    }

    /**
     * Get Media ThumbConfiguration.
     */
    public function mediaThumbs(Website $website, Media\Media $media): array
    {
        $namespaces = [];
        $excludes = [Media\Media::class, Configuration::class];
        $meta = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();

        foreach ($meta as $metaEntity) {
            $associationsMapping = $this->coreLocator->em()->getClassMetadata($metaEntity->getName())->getAssociationMappings();
            $haveMedias = !empty($associationsMapping['mediaRelation']) || !empty($associationsMapping['mediaRelations']);
            $repository = $haveMedias ? $this->coreLocator->em()->getRepository($metaEntity->getName()) : null;
            if ($haveMedias && !in_array($metaEntity->getName(), $excludes)) {
                $relation = !empty($associationsMapping['mediaRelation']) ? 'mediaRelation' : 'mediaRelations';
                $existing = $repository->createQueryBuilder('e')->select('e')
                    ->leftJoin('e.'.$relation, 'mr')
                    ->andWhere('mr.media = :media')
                    ->setParameter('media', $media)
                    ->addSelect('mr')
                    ->getQuery()
                    ->getResult();
                if ($existing) {
                    foreach ($existing as $item) {
                        if ($item instanceof Layout\Block) {
                            $layout = $item->getCol()->getZone()->getLayout();
                            $layoutAssociationsMapping = $this->coreLocator->em()->getClassMetadata(Layout\Layout::class)->getAssociationMappings();
                            foreach ($layoutAssociationsMapping as $layoutAssociation) {
                                $referLayoutEntity = new $layoutAssociation['targetEntity']();
                                if (method_exists($referLayoutEntity, 'getLayout') && !$referLayoutEntity instanceof Layout\Page && !$referLayoutEntity instanceof Layout\Zone) {
                                    $layoutEntity = $this->coreLocator->em()->getRepository($layoutAssociation['targetEntity'])->findOneBy(['layout' => $layout]);
                                    if ($layoutEntity) {
                                        $namespaces[] = [
                                            'entity' => $layoutEntity,
                                            'classname' => $layoutAssociation['targetEntity'],
                                        ];
                                    }
                                }
                            }
                        }
                        $namespaces[] = [
                            'entity' => $item,
                            'classname' => $metaEntity->getName(),
                        ];
                    }
                }
            }
        }

        return $this->coreLocator->em()->getRepository(Media\ThumbConfiguration::class)->findByNamespaces($namespaces, $website->getConfiguration());
    }

    /**
     * Check if MediaRelation is in box.
     */
    private function inBox(?Website $website = null, ?string $extension = null, ?MediaModel $mediaModel = null): void
    {
        $this->arguments['inBox'] = false;
        if (isset($this->options['isInBox'])) {
            $this->arguments['inBox'] = $this->options['isInBox'];
        } elseif ($website) {
            $exceptionExceptions = !empty($this->arguments['thumbnails']) ? $this->arguments['thumbnails']['extensionsExceptions'] : ['svg', 'gif', 'tiff'];
            $allowedExtensions = !empty($this->arguments['allowedExtensions']) ? $this->arguments['thumbnails']['allowedExtensions'] : ['jpg', 'jpeg', 'png', 'webp'];
            $allExtensions = array_merge($exceptionExceptions, $allowedExtensions);
            $allowed = $this->arguments['mediaRelation']
                && !$this->arguments['inAdmin']
                && !isset($this->options['notInBox'])
                && in_array($extension, $allExtensions);
            $mediaRelationIntl = $mediaModel instanceof MediaModel ? $mediaModel->intl : null;
            if ($allowed && $mediaModel instanceof MediaModel && ($mediaModel->downloadable || $mediaModel->popup)
                || $allowed && $mediaRelationIntl instanceof IntlModel && ($mediaRelationIntl->link || $mediaRelationIntl->linkTargetPage)) {
                $this->arguments['inBox'] = true;
            } else {
                $this->arguments['inBox'] = false;
            }
        }
        if ($mediaModel instanceof MediaModel && $mediaModel->hideHover) {
            $this->arguments['inBox'] = false;
        }
    }

    /**
     * Get attributes style.
     *
     * @throws NonUniqueResultException|MappingException|LoaderError|RuntimeError|SyntaxError
     */
    private function getAttributeStyle(?MediaModel $mediaModel = null, array $thumb = [], array $options = []): string
    {
        $style = [];
        $options['forceThumb'] = true;
        $entity = !empty($options['entity']) ? $options['entity'] : null;
        $bgFullSize = is_object($entity) && method_exists($entity, 'isBackgroundFullSize') ? $entity->isBackgroundFullSize()
            : (is_array($entity) && isset($entity['backgroundFullSize']) ? $entity['backgroundFullSize'] : false);
        $hexadecimalBg = $bgFullSize && is_object($entity) && method_exists($entity, 'getHexadecimalCode') && $entity->getHexadecimalCode() ? ' '.$entity->getHexadecimalCode()
            : ($bgFullSize && is_array($entity) && !empty($entity['hexadecimalCode']) ? ' '.$entity['hexadecimalCode'] : '');
        $thumbnails = $mediaModel instanceof MediaModel && !str_contains($mediaModel->media->getFilename(), 'placeholder') ? $this->imageThumbnail->execute($mediaModel, $thumb, $options) : [];

        if (!empty($thumbnails['files'])) {
            $retinaSize = $this->imageThumbnail->getRetinaSizes();
            $mediaQueries = [
                1236 => 'mobile',
                1982 => 'tablet',
                3840 => 'desktop',
            ];
            foreach ($thumbnails['files'] as $size => $path) {
                if (in_array($size, $retinaSize)) {
                    if (!empty($options[$mediaQueries[$size]]) && !is_object($options[$mediaQueries[$size]])) {
                        $filesystem = new Filesystem();
                        $fileDirname = $this->projectDirname.'/public'.$options[$mediaQueries[$size]];
                        $fileDirname = str_replace([$this->request->getSchemeAndHttpHost()], '', $fileDirname);
                        $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);
                        $fileDirname = $filesystem->exists($fileDirname) && !is_dir($fileDirname) ? $fileDirname : $this->projectDirname.'/public/medias/placeholder.jpg';
                        $file = new File($fileDirname);
                        $media = new Media\Media();
                        $media->setFilename($file->getFilename());
                        $media->setExtension($file->getExtension());
                        $media->setScreen('desktop');
                        $media->setWebsite($this->coreLocator->website()->entity);
                        $mediaRelation = new Media\MediaRelation();
                        $mediaRelation->setLocale($this->request->getLocale());
                        $mediaRelation->setMedia($media);
                        $media = MediaModel::fromEntity($mediaRelation, $this->coreLocator);
                        $options[$mediaQueries[$size]] = $this->file($media, [], ['path' => true, 'file' => $file]);
                    }
                    $path = !empty($options[$mediaQueries[$size]]) ? $options[$mediaQueries[$size]] : $path;
                    $path = $path && !str_contains($path, $this->coreLocator->schemeAndHttpHost()) ? $this->coreLocator->schemeAndHttpHost().$path : $path;
                    $style[] = ['size' => $size, 'screen' => $mediaQueries[$size], 'style' => 'background:'.$hexadecimalBg.' url("'.$path.'");'];
                }
            }
            if (empty($style) && !empty($thumbnails['files'][1920])) {
                foreach ($mediaQueries as $size => $screen) {
                    $path = !empty($options[$screen]) ? $options[$screen] : $thumbnails['files'][1920];
                    $path = $path && !str_contains($path, $this->coreLocator->schemeAndHttpHost()) ? $this->coreLocator->schemeAndHttpHost().$path : $path;
                    $style[] = ['size' => $size, 'screen' => $screen, 'style' => 'background:'.$hexadecimalBg.' url("'.$path.'");'];
                }
            }
        }

        return json_encode($style);
    }

    /**
     * To set placeholder.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function setPlaceholder(?MediaModel $mediaModel = null, array $options = []): ?MediaModel
    {
        if ((!$mediaModel && isset($options['placeholder']) && $options['placeholder'])
            || !$mediaModel && isset($options['src'])
            || ($mediaModel instanceof MediaModel && $mediaModel->media && !$mediaModel->media->getFilename() && isset($options['placeholder']) && $options['placeholder'])) {
            $inAdmin = (isset($options['inAdmin']) && true === (bool)$options['inAdmin']) || (isset($this->arguments['inAdmin']) && true === (bool)$this->arguments['inAdmin']);
            $filename = $inAdmin ? 'placeholder-back.jpg' : 'placeholder.jpg';
            $media = new Media\Media();
            $media->setFilename('medias/'.$filename);
            $media->setExtension('jpg');
            $media->setScreen('desktop');
            $media->setName('placeholder');
            $media->setWebsite($this->arguments['website']);
            $mediaRelation = new Media\MediaRelation();
            $mediaRelation->setLocale($this->request->getLocale());
            $mediaRelation->setMedia($media);
            $mediaModel = MediaModel::fromEntity($mediaRelation, $this->coreLocator);
        }

        return $mediaModel;
    }

    /**
     * To check if is as default placeholder.
     */
    private function asPlaceholder(?MediaModel $mediaModel = null): bool
    {
        if ($mediaModel->media && $mediaModel->media->getFilename() && str_contains($mediaModel->media->getFilename(), 'placeholder.jpg')) {
            return in_array($mediaModel->media->getFilename(), ['placeholder.jpg', 'placeholder-dark.jpg']);
        }
        return false;
    }
}
