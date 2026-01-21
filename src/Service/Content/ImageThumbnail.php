<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Website;
use App\Entity\Media;
use App\Model\IntlModel;
use App\Model\MediaModel;
use App\Service\Core\FileInfo;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Content\BrowserRuntime;
use Doctrine\ORM\NonUniqueResultException;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;
use Symfony\Component\Yaml\Yaml;

/**
 * ImageThumbnail.
 *
 * Manage image crop
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImageThumbnail implements ImageThumbnailInterface
{
    private const bool ACTIVE_WEBP = true;
    private const bool ACTIVE_AVIF = false;
    private const bool ALWAYS_WEBP = true;
    private const bool LAZY_SVG_DATA = false;
    private const bool LAZY_ORIGINAL = true;
    private const bool FORCE_QUALITY = false;
    private const int MAX_FILE_SIZE_OPTIMIZATION = 500 * 1024; // octets 500k
    private const int MAX_FILE_SIZE = 3145728; // octets 3145728 = 3M : https://www.convertworld.com/fr/mesures-informatiques/megaoctet-megabyte.html
    private const int MAX_FILE_WIDTH = 4000; // pixels 4000
    private const int MAX_FILE_HEIGHT = 6000; // pixels 6000
    private const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const array EXCEPTIONS_EXTENSIONS = ['svg', 'gif', 'tiff', 'raw', 'heic'];
    private const array CONTAINER_SIZE = [
        1920 => 1391,
    ];
    private const array SIZES = [618, 991, 1920];
    private const array RETINA_SIZES = [1236, 1982, 3840];
    private const array SCREENS_SIZES = [
        'mobile' => [618, 1236],
        'tablet' => [991, 1982],
        'desktop' => [1920, 3840],
    ];
    private const array SCREENS_SIZES_ATTR = [
        'mobile' => 618,
        'tablet' => 991,
        'desktop' => 1920,
    ];

    private ?Request $request;
    private ?string $schemeAndHttpHost;
    private ?string $screen = null;
    private string $projectDirname;
    private ?string $uploadDirname = '';
    private array $yamlConfig = [];
    private array $cache = [];
    private bool $generator = false;
    private bool $inAdmin;
    private ?bool $webpSupport = null;
    private ?bool $avifSupport = null;
    private array $screensSizes;
    private Filesystem $filesystem;

    /**
     * ImageThumbnail constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly FilterService $filterService,
        private readonly BrowserRuntime $browserRuntime,
    ) {
        $this->request = $this->coreLocator->request();
        $this->schemeAndHttpHost = $this->request instanceof Request ? $this->request->getSchemeAndHttpHost() : null;
        $this->screen = $this->request instanceof Request && !$this->screen ? $this->browserRuntime->screen() : 'desktop';
        $this->projectDirname = $this->coreLocator->projectDir();
        $this->filesystem = new Filesystem();
        $this->inAdmin = is_object($this->request) && method_exists($this->request, 'getUri')
            && preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $this->request->getUri());
        $this->getScreenSizes();
    }

    /**
     * To set webp support.
     */
    private function isWebpSupported(): bool
    {
        if (null !== $this->webpSupport) {
            return $this->webpSupport;
        }

        if (self::ALWAYS_WEBP) {
            return $this->webpSupport = true;
        }

        $session = $this->request?->hasSession() ? $this->request->getSession() : null;
        if ($session?->has('WEBP_SUPPORT')) {
            return $this->webpSupport = $session->get('WEBP_SUPPORT');
        }

        $this->webpSupport = !empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'image/webp');

        if ($session) {
            $session->set('WEBP_SUPPORT', $this->webpSupport);
        }

        return $this->webpSupport;
    }

    private function isAvifSupported(): bool
    {
        if (!self::ACTIVE_AVIF) {
            return $this->avifSupport = false;
        }

        if (null !== $this->avifSupport) {
            return $this->avifSupport;
        }

        $session = $this->request?->hasSession() ? $this->request->getSession() : null;
        if ($session?->has('AVIF_SUPPORT')) {
            return $this->avifSupport = $session->get('AVIF_SUPPORT');
        }

        $this->avifSupport = !empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'image/avif') && function_exists('imageavif');

        if ($session) {
            $session->set('AVIF_SUPPORT', $this->avifSupport);
        }

        return $this->avifSupport;
    }

    /**
     * Get image dimensions with the internal cache.
     */
    private function getImageDimensions(string $path): array
    {
        if (isset($this->cache[$path]['dimensions'])) {
            return $this->cache[$path]['dimensions'];
        }

        if ($this->filesystem->exists($path) && !is_dir($path)) {
            $dimensions = @getimagesize($path);
            if ($dimensions) {
                return $this->cache[$path]['dimensions'] = [
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                ];
            }
        }

        return $this->cache[$path]['dimensions'] = ['width' => 0, 'height' => 0];
    }

    /**
     * To execute service.
     */
    public function execute(?MediaModel $mediaModel = null, array $thumbs = [], array $options = [], bool $generator = false): mixed
    {
        if ($mediaModel && 'file' === $mediaModel->type) {
            return false;
        }

        $this->setDefault($options);

        $thumbnails = [];
        $thumbnails['extensionsExceptions'] = self::EXCEPTIONS_EXTENSIONS;
        $thumbnails['allowedExtensions'] = self::ALLOWED_EXTENSIONS;
        $asMediaModel = $mediaModel instanceof MediaModel;
        $media = $asMediaModel ? $mediaModel->media : $mediaModel;
        $website = $asMediaModel && $media->getWebsite() ? $media->getWebsite() : $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($this->schemeAndHttpHost)->entity;
        $this->uploadDirname = $website instanceof Website ? $website->getUploadDirname() : null;
        $file = !empty($options['file']) ? $options['file'] : null;
        $fileDirname = $file instanceof File ? $file->getPathname() : null;
        $originalDirname = $options['originalSrc'] = $fileDirname ?: ((str_contains($media->getFilename(), '/build/') || str_contains($media->getFilename(), '/medias/'))
            ? $this->dirname($this->projectDirname.'/public'.$media->getFilename()) : $this->dirname($this->projectDirname.'/public/uploads/'.$this->uploadDirname.'/'.$media->getFilename()));
        $originalExist = $this->filesystem->exists($originalDirname);
        $originalInfoFile = $media->getFilename() ? $this->coreLocator->fileInfo()->file($website, $media->getFilename(), $originalDirname) : null;
        $isEnableMaxSizes = $originalInfoFile && $originalInfoFile->getWidth() <= self::MAX_FILE_WIDTH && $originalInfoFile->getHeight() <= self::MAX_FILE_HEIGHT && $originalInfoFile->getSize() <= self::MAX_FILE_SIZE;
        $mediaRelation = $options['mediaRelation'] = !empty($options['mediaRelation']) ? $options['mediaRelation'] : ($asMediaModel ? $mediaModel->mediaRelation : null);
        $execute = $infoFile = false;

        if ($media instanceof Media\Media && $media->getFilename()) {
            $extension = $this->getExtension($media);
            if ($extension && ('desktop' === $media->getScreen() || 'poster' === $media->getScreen())) {
                $publicDir = $this->projectDirname.'/public';
                $placeholderBack = $this->dirname($publicDir.'/medias/placeholder-back.jpg');
                $placeholderDefault = $this->dirname($publicDir.'/medias/placeholder.jpg');

                foreach ($this->screensSizes as $size) {
                    $screen = $this->screen($size, true);
                    $screenMedia = $asMediaModel && !$this->inAdmin ? $this->getScreenMedia($screen, $media) : $media;
                    $filename = $screenMedia->getFilename();
                    $dirname = $fileDirname ?: ((str_contains($filename, '/build/') || str_contains($filename, '/medias/'))
                        ? $this->dirname($publicDir.$filename) : $this->dirname($publicDir.'/uploads/'.$this->uploadDirname.'/'.$filename));
                    
                    if (!$this->filesystem->exists($dirname)) {
                        $dirname = $this->inAdmin ? $placeholderBack : $placeholderDefault;
                    }

                    $asLoader = $options['loader'] ?? false;
                    $isEnableSize = in_array($size, self::SIZES) || in_array($size, self::RETINA_SIZES);
                    $isEnableMedia = !$mediaRelation || !$mediaRelation->getId() || ($mediaRelation->getCacheDate() instanceof \DateTime && !$asLoader);
                    $isEnableEnv = $generator || ($this->inAdmin && !isset($options['loader'])) || ($options['forceThumb'] ?? false);
                    $infoFile = $options['sizeInfo'] = $this->coreLocator->fileInfo()->file($website, $media->getFilename(), $dirname);

                    if (!$isEnableSize) {
                        continue;
                    }

                    $fileExist = $originalDirname === $dirname ? $originalExist : $this->filesystem->exists($dirname);
                    $sizeAllowed = ($fileExist && $infoFile->getSize() <= self::MAX_FILE_SIZE
                            && $infoFile->getWidth() <= self::MAX_FILE_WIDTH
                            && $infoFile->getHeight() <= self::MAX_FILE_HEIGHT)
                        || str_contains($originalDirname, 'placeholder.jpg')
                        || str_contains($originalDirname, 'placeholder.jpeg');
                    $execute = $isEnableMedia || $isEnableEnv;
                    $execute = $execute && $sizeAllowed;
                    try {
                        $thumb = $this->getScreenThumb($screenMedia, $mediaRelation, $thumbs, $screen, $dirname, $size, $options);
                        $thumb = $mediaRelation ? $this->setRatio($mediaRelation, $thumb, $size, $options) : $thumb;
                        $runtimeConfig = $this->getRuntimeConfig($thumb->thumb, $size, $options);
                        $thumbnails['runtimeConfig'][$size] = $runtimeConfig;
                        $thumbnails['thumbs'][$size] = $thumb;
                        if ($execute) {
                            $thumbnails['files'][$size] = $this->publicPath($this->getThumbnail($thumb, $runtimeConfig, null, $options, $size));
                        } else {
                            $thumbnails['files'][$size] = $this->publicPath($dirname);
                        }
                        if (isset($options['strictSize']) && $options['strictSize'] && isset($options['path']) && $options['path'] && isset($options['filter']) && $options['filter']) {
                            return !str_contains($thumbnails['files'][$size], $this->coreLocator->schemeAndHttpHost())
                                ? $this->coreLocator->schemeAndHttpHost().rtrim($thumbnails['files'][$size], '/')
                                : $thumbnails['files'][$size];
                        }
                    } catch (\Exception $e) {
                    }
                }
                foreach (['runtimeConfig', 'thumbs', 'files'] as $key) {
                    if (!empty($thumbnails[$key])) {
                        ksort($thumbnails[$key]);
                    }
                }
            }
        }
        $currentSize = self::SCREENS_SIZES_ATTR[$this->screen];

        if ((!$isEnableMaxSizes && $media->getFilename()) || ($infoFile && self::MAX_FILE_SIZE_OPTIMIZATION < $infoFile->getSize())) {
            if ($this->coreLocator->authorizationChecker()->isGranted('ROLE_ADMIN')) {
                $thumbnails = $this->largeFile($thumbnails, $originalInfoFile);
            }
        }

        $mediaRelationIntl = $asMediaModel ? $mediaModel->intl : null;
        $mediaIntl = $asMediaModel ? $mediaModel->mediaIntl : null;
        $currentRuntimeInfos = $this->getCurrentRuntime($thumbnails, $currentSize);
        $currentRuntime = $currentRuntimeInfos['runtimeConfig'];
        $thumbnails['currentScreen'] = $this->screen;
        $thumbnails['sizesDisplay'] = self::SCREENS_SIZES[$this->screen];
        $currentSize = $thumbnails['currentSize'] = $currentRuntimeInfos['currentSize'];
        $thumbnails['dataSource'] = $thumbnails['currentFile'] = !empty($thumbnails['files'][$currentSize]) ? $thumbnails['files'][$currentSize] : (!empty($thumbnails['files']) ? end($thumbnails['files']) : null);
        $thumbnails['currentRetinaFile'] = !empty($thumbnails['files'][$currentSize * 2]) ? $thumbnails['files'][$currentSize * 2] : null;
        $thumbnails['originalSrc'] = $originalExist && !$file instanceof File ? '/uploads/'.$this->uploadDirname.'/'.$media->getFilename()
            : ('cms-component' === $media->getCategory() ? $media->getFilename() : ($file instanceof File ? str_replace([$this->projectDirname, '\\', '/public'], ['', '/', ''], $fileDirname) : '/medias/placeholder.jpg'));
        $thumbnails['lazyFile'] = !isset($options['loader']) ? $this->getLazy($thumbnails, $currentSize, $options) : null;
        if (isset($options['lazyFiles']) && true === (bool) $options['lazyFiles']) {
            foreach (self::SIZES as $lazySize) {
                if (!$this->inAdmin || 1920 === $lazySize) {
                    $thumbnails['lazyFiles'][$lazySize]['src'] = $this->getLazy($thumbnails, $lazySize, $options);
                    $matches = $thumbnails['lazyFiles'][$lazySize]['src'] ? explode('.', $thumbnails['lazyFiles'][$lazySize]['src']) : [];
                    $thumbnails['lazyFiles'][$lazySize]['extension'] = end($matches);
                }
            }
        }
        $thumbnails = $media->getFilename() ? $this->infos($originalInfoFile, $thumbnails, $media, $currentRuntime, $mediaModel, $mediaIntl, $mediaRelationIntl, $options) : $thumbnails;
        $info = !empty($thumbnails['infos']) ? $thumbnails['infos'] : null;

        //        if (isset($options['loader']) || self::LAZY_SVG_DATA && $mediaModel && 'svg' === $mediaModel->extension) {
        $thumbnails['lazyFileSvg'] = 'data:image/svg+xml,%3Csvg width="'.$info['width'].'" height="'.$info['height'].'" xmlns="http://www.w3.org/2000/svg"%3E%3Crect x="0" y="0" width="'.$info['width'].'" height="'.$info['height'].'" fill="none"/%3E%3C/svg%3E';
        //        }

        if (!$this->inAdmin && !isset($options['filter']) && $mediaRelation && $execute && !$mediaRelation->getCacheDate() instanceof \DateTime && $mediaRelation->getId()) {
            $mediaRelation = $this->coreLocator->em()->getRepository(get_class($mediaRelation))->find($mediaRelation->getId());
            $mediaRelation->setCacheDate(new \DateTime('now'));
            $this->coreLocator->em()->persist($mediaRelation);
            $this->coreLocator->em()->flush();
        }

        if (!empty($options['screensSizes']) && !empty($options['priority']) && 'high' === $options['priority'] && !empty($thumbnails['files'])) {
            foreach ($thumbnails['files'] as $file) {
                $linkProvider = $this->coreLocator->request()->attributes->get('_links', new GenericLinkProvider());
                $this->coreLocator->request()->attributes->set('_links', $linkProvider->withLink(
                    (new Link('preload', $file))->withAttribute('as', 'image')
                ));
            }
        }

        return $this->attributes($mediaRelation, $thumbnails, $currentSize, $options);
    }

    /**
     * To get default vars.
     */
    private function setDefault(array $options = []): void
    {
        $this->inAdmin = isset($options['inAdmin']) ? (bool) $options['inAdmin'] : $this->inAdmin;
        $this->screensSizes = array_merge(self::SIZES, self::RETINA_SIZES);
        if (!$this->generator && $this->inAdmin) {
            $this->screensSizes = [1920];
        }
    }

    /**
     * To get Thumb by screen.
     */
    private function getScreenThumb(
        Media\Media $media,
        mixed $mediaRelation,
        array $thumbs,
        string $screen,
        string $dirname,
        int $size,
        array $options = []
    ): object {

        $thumbConfiguration = !empty($thumbs[$screen]) ? $thumbs[$screen] : null;
        if (!$thumbConfiguration) {
            foreach ($thumbs as $thumbConfiguration) {
                break;
            }
        }

        $isRetinaSize = in_array($size, self::RETINA_SIZES);
        $retinaSet = false;

        if (!empty($options['screensSizes'])) {
            foreach ($options['screensSizes'] as $screen => $sizes) {
                if (in_array($size, self::SCREENS_SIZES[$screen])) {
                    $options['maxWidth'] = !empty($sizes['width']) ? $sizes['width'] : null;
                    $options['maxHeight'] = !empty($sizes['height']) ? $sizes['height'] : null;
                }
            }
        }

        $optionMaxWidth = !empty($options['maxWidth']) ? intval($options['maxWidth']) : null;
        if ($isRetinaSize && !empty($optionMaxWidth)) {
            $optionMaxWidth = (int) ceil($optionMaxWidth * 2);
            $retinaSet = true;
        }
        $optionMaxHeight = !empty($options['maxHeight']) ? intval($options['maxHeight']) : null;
        if ($isRetinaSize && !empty($optionMaxHeight)) {
            $optionMaxHeight = (int) ceil($optionMaxHeight * 2);
            $retinaSet = true;
        }

        if (('desktop' === $media->getScreen() || !$media->getId()) && $thumbConfiguration instanceof Media\ThumbConfiguration) {
            foreach ($media->getThumbs() as $mediaThumb) {
                if ($mediaThumb->getConfiguration()->getId() === $thumbConfiguration->getId() && ($mediaThumb->getWidth() > 0 || $mediaThumb->getHeight() > 0)) {
                    $thumbInfo = $this->setThumbInfos($media, $screen, $dirname, $size, $thumbConfiguration->getWidth(), $thumbConfiguration->getHeight(), $options, $thumbConfiguration);
                    $thumb = $thumbInfo->thumb;
                    $width = $mediaThumb->getWidth();
                    $height = $mediaThumb->getHeight();
                    $dataX = $isRetinaSize && is_numeric($mediaThumb->getDataX()) ? $mediaThumb->getDataX() * 2 : $mediaThumb->getDataX();
                    $dataY = $isRetinaSize && is_numeric($mediaThumb->getDataY()) ? $mediaThumb->getDataY() * 2 : $mediaThumb->getDataY();
                    $scale = $isRetinaSize ? 2 : 1;
                    if ($mediaThumb->getWidth() > $size) {
                        $width = $size;
                        $height = (int) ceil(($mediaThumb->getHeight() * $width) / $mediaThumb->getWidth());
                        $dataX = (int) ceil(($width * $mediaThumb->getDataX()) / $mediaThumb->getWidth());
                        $dataY = (int) ceil(($height * $mediaThumb->getDataY()) / $mediaThumb->getHeight());
                        $scale = $size / $mediaThumb->getWidth();
                    }
                    $thumbConfiguration = $thumb->getConfiguration();
                    $thumb->setWidth($width);
                    $thumb->setHeight($height);
                    $thumb->setDataX($dataX);
                    $thumb->setDataY($dataY);
                    $thumb->setRotate($mediaThumb->getRotate());
                    $thumb->setScale($scale);
                    $thumb->setScaleX($mediaThumb->getScaleX());
                    $thumb->setScaleY($mediaThumb->getScaleY());
                    $thumbConfiguration->setWidth($mediaThumb->getWidth());
                    $thumbConfiguration->setHeight($mediaThumb->getHeight());

                    return $thumbInfo;
                }
            }
        }
        $methodWidth = 'desktop' === $screen ? 'getMaxWidth' : 'get'.ucfirst($screen).'MaxWidth';
        $methodHeight = 'desktop' === $screen ? 'getMaxHeight' : 'get'.ucfirst($screen).'MaxHeight';
        $width = $mediaRelation && $mediaRelation->$methodWidth() ? $mediaRelation->$methodWidth() : ($mediaRelation && $mediaRelation->getMaxWidth() ? $mediaRelation->getMaxWidth()
            : ($thumbConfiguration ? $thumbConfiguration->getWidth() : (!empty($optionMaxWidth) ? $optionMaxWidth : null)));
        $height = $mediaRelation && $mediaRelation->$methodHeight() ? $mediaRelation->$methodHeight() : ($mediaRelation && $mediaRelation->getMaxHeight() ? $mediaRelation->getMaxHeight()
            : ($thumbConfiguration ? $thumbConfiguration->getHeight() : (!empty($optionMaxHeight) ? $optionMaxHeight : null)));

        if (!$thumbConfiguration && ($width || $height)) {
            $newThumb = new Media\ThumbConfiguration();
            $newThumb->setWidth($width);
            $newThumb->setHeight($height);
            $thumbConfiguration = $newThumb;
        }

        if ($thumbConfiguration && $thumbConfiguration->getWidth()
            && in_array($thumbConfiguration->getWidth(), self::SIZES)
            && in_array($thumbConfiguration->getWidth(), self::SCREENS_SIZES[$screen])
            && $thumbConfiguration->getWidth() < $size && is_numeric($thumbConfiguration->getHeight())) {
            $height = (int) ceil(($size * $thumbConfiguration->getHeight()) / $thumbConfiguration->getWidth());
            $width = $size;
            $newThumb = new Media\ThumbConfiguration();
            $newThumb->setWidth($width);
            $newThumb->setHeight($height);
            $newThumb->setFixedHeight($thumbConfiguration->isFixedHeight());
            $thumbConfiguration = $newThumb;
        }

        if (($media->getId() && 'mobile' === $media->getScreen()) || ($media->getId() && 'tablet' === $media->getScreen())) {
            $dimensions = $this->getImageDimensions($dirname);
            $originalWidth = $dimensions['width'];
            $originalHeight = $dimensions['height'];
            $height = $originalWidth > $width ? (int) ceil(($originalHeight * $width) / $originalWidth) : $originalHeight;
            $width = $originalWidth > $width ? $width : $originalWidth;
            if ($thumbConfiguration instanceof Media\ThumbConfiguration) {
                $thumbConfiguration->setWidth($width);
                $thumbConfiguration->setHeight($height);
            }
        }

        if ($thumbConfiguration instanceof Media\ThumbConfiguration) {
            if ($isRetinaSize && !$retinaSet) {
                $newThumb = new Media\ThumbConfiguration();
                $newThumb->setHeight($height);
                $newThumb->setFixedHeight($thumbConfiguration->isFixedHeight());
                $width = is_numeric($width) && $width > 0 ? (int) ceil($width * 2) : $thumbConfiguration->getWidth();
                $newThumb->setWidth($width);
                $height = is_numeric($height) && $height > 0 ? (int) ceil($height * 2) : $thumbConfiguration->getHeight();
                $newThumb->setHeight($height);
                $dimensions = $this->getImageDimensions($dirname);
                $originalWidth = $dimensions['width'];
                if ($originalWidth < $newThumb->getWidth() && $newThumb->getHeight() > 0) {
                    $height = (int) ceil(($newThumb->getHeight() * $originalWidth) / $newThumb->getWidth());
                    $newThumb->setHeight($height);
                    $newThumb->setWidth($originalWidth);
                }
                $thumbConfiguration = $newThumb;
            }
            return $this->setThumbInfos($media, $screen, $dirname, $size, $width, $height, $options, $thumbConfiguration);
        }

        if (!empty($options['screensSizes'][$screen])) {
            $width = !empty($options['screensSizes'][$screen]['width']) ? intval($options['screensSizes'][$screen]['width']) : null;
            $height = !empty($options['screensSizes'][$screen]['height']) ? intval($options['screensSizes'][$screen]['height']) : null;
            return $this->setThumbInfos($media, $screen, $dirname, $size, $width, $height, $options);
        }

        $filename = $media->getFilename();
        if ($filename) {
            $width = !empty($options['width']) ? $options['width'] : (!empty($optionMaxWidth) ? $optionMaxWidth : null);
            $height = !empty($options['height']) ? $options['height'] : (!empty($optionMaxHeight) ? $optionMaxHeight : null);
            if (!empty($options['filter'])) {
                if (!$this->yamlConfig) {
                    $yamlDirname = $this->projectDirname.'/config/packages/liip_imagine.yaml';
                    $yamlDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $yamlDirname);
                    $this->yamlConfig = Yaml::parseFile($yamlDirname);
                }
                $yamlSizes = $this->getYamlSizes($options['filter']);
                $width = !empty($yamlSizes['width']) ? $yamlSizes['width'] : $width;
                $height = !empty($yamlSizes['height']) ? $yamlSizes['height'] : $height;
            }
            if ($isRetinaSize && !$retinaSet && $width > 0 && $height > 0) {
                $width = (int) ceil($width * 2);
                $height = (int) ceil($height * 2);
                $dimensions = $this->getImageDimensions($dirname);
                $originalWidth = $dimensions['width'];
                $originalHeight = $dimensions['height'];
                if ($originalHeight < $height) {
                    $width = null;
                    $height = $originalHeight;
                }
                if ($originalWidth < $width) {
                    $width = $originalWidth;
                    $height = null;
                }
            }
            return $this->setThumbInfos($media, $screen, $dirname, $size, $width, $height, $options);
        }

        return (object) [];
    }

    private function setThumbInfos(
        Media\Media $media,
        string $screen,
        string $dirname,
        int $size,
        ?int $width = null,
        ?int $height = null,
        array $options = [],
        ?Media\ThumbConfiguration $thumbConfiguration = null
    ): object {

        $cropInfos = $this->cropInfos($media, $dirname, $size, $width, $height, $options);
        $thumb = new Media\Thumb();
        $thumb->setWidth($cropInfos->width);
        $thumb->setHeight($cropInfos->height);
        $configuration = new Media\ThumbConfiguration();
        $configuration->setWidth($cropInfos->width);
        $configuration->setHeight($cropInfos->height);
        $configuration->setScreen($screen);
        $configuration->setFixedHeight($thumbConfiguration ? $thumbConfiguration->isFixedHeight() : false);
        $thumb->setConfiguration($configuration);
        $thumb->setMedia($media);

        return (object) [
            'thumb' => $thumb,
            'media' => $media,
            'cropInfos' => $cropInfos,
        ];
    }

    /**
     * To get crop sizes.
     */
    private function cropInfos(
        Media\Media $media,
        string $dirname,
        int $size,
        ?int $width = null,
        ?int $height = null,
        array $options = []): object
    {
        $dimensions = $this->getImageDimensions($dirname);
        $originalWidth = $dimensions['width'];
        $originalHeight = $dimensions['height'];
        $svgSizes = [];

        if (!isset($options['strictSize']) || !$options['strictSize']) {
            $initWith = $width;
            $width = !$initWith && !$height ? $originalWidth : $initWith;
            $height = !$height && !$initWith ? $originalHeight : $height;
            $svgSizes = 'svg' === $media->getExtension() ? $this->svgSizes($media, $dirname, $width, $height) : [];
            if ($originalWidth && $originalWidth < $width) {
                $height = $height ? ($height * $originalWidth) / $width : $height;
                $width = $originalWidth;
            } elseif ($originalWidth && $width && $width > $size) {
                $height = $height ? ($size * $height) / $width : $height;
                $width = $size;
            } elseif ($originalHeight && $originalHeight < $height) {
                $width = $width ? ($width * $originalHeight) / $height : $width;
                $height = $originalHeight;
            }
            if ($originalWidth && $width && $width > $size) {
                $width = $size;
                $height = ($originalHeight * $width) / $originalWidth;
            }
        }

        $matches = $dirname ? explode('.', $dirname) : [];

        return (object) [
            'dirname' => $dirname,
            'extension' => end($matches),
            'svgSizes' => $svgSizes,
            'originalWidth' => $originalWidth,
            'originalHeight' => $originalHeight,
            'width' => $width ? (int) (ceil($width)) : $width,
            'height' => $height ? (int) (ceil($height)) : $height,
        ];
    }

    /**
     * To set screen Ration.
     */
    private function setRatio(object $mediaRelation, object $thumbInfos, int $size, array $options = []): object
    {
        $thumb = $thumbInfos->thumb;
        $thumbConfiguration = $thumb->getConfiguration();
        $initCropWidth = $thumbConfiguration->getWidth();
        $width = $thumbConfiguration->getWidth();
        $height = $thumbConfiguration->getHeight();

        $colSize = !empty($options['colSize']) ? intval($options['colSize']) : 12;
        $asCrop = is_numeric($thumb->getDataX()) || is_numeric($thumb->getDataY()) || is_numeric($mediaRelation->getMaxWidth()) || is_numeric($mediaRelation->getMaxHeight());
        if ($width && $height && !$asCrop && $initCropWidth && $colSize && $colSize < 12 && in_array($size, self::SCREENS_SIZES['desktop'])) {
            $isRetinaSize = in_array($size, self::RETINA_SIZES);
            $containerSize = $isRetinaSize ? self::CONTAINER_SIZE[$size / 2] : self::CONTAINER_SIZE[$size];
            $colRatio = 12 / $colSize;
            $ratioMaxWidth = (int) ceil($containerSize / $colRatio);
            if ($thumbInfos->thumb->getWidth() > $ratioMaxWidth) {
                if (!$isRetinaSize) {
                    $height = (int) ceil(($height * $ratioMaxWidth) / $width);
                    $width = $ratioMaxWidth;
                }
            }
        }

        if ($initCropWidth !== $width) {
            $thumb->setWidth($width);
            $thumb->setHeight($height);
            $thumbConfiguration->setWidth($width);
            $thumbConfiguration->setHeight($height);
            $thumbInfos = (array) $thumbInfos;
            $thumbInfos['cropInfos'] = (array) $thumbInfos['cropInfos'];
            $thumbInfos['cropInfos']['width'] = $width;
            $thumbInfos['cropInfos']['height'] = $height;
            $thumbInfos['cropInfos'] = (object) $thumbInfos['cropInfos'];
            $thumbInfos = (object) $thumbInfos;
        }

        return (object) [
            'thumb' => $thumbInfos->thumb,
            'media' => $thumbInfos->media,
            'cropInfos' => $thumbInfos->cropInfos,
        ];
    }

    /**
     * To get current runtime.
     */
    private function getCurrentRuntime(array $thumbnails, int $currentSize): array
    {
        $runtimeConfigs = !empty($thumbnails['runtimeConfig']) ? $thumbnails['runtimeConfig'] : [];
        $runtimeConfig = !empty($runtimeConfigs[$currentSize]) ? $runtimeConfigs[$currentSize] : [];

        return [
            'runtimeConfig' => $runtimeConfig,
            'currentSize' => $currentSize,
        ];
    }

    /**
     * To set extension.
     */
    private function getExtension(?Media\Media $media = null): ?string
    {
        if (!$media instanceof Media\Media) {
            return null;
        }

        if ($media->getExtension()) {
            return $media->getExtension();
        }

        $filename = $media->getFilename();
        if ($filename) {
            return pathinfo($filename, PATHINFO_EXTENSION);
        }

        return null;
    }

    /**
     * To set a screen.
     */
    private function screen(int $size, bool $asReturn = false): mixed
    {
        foreach (self::SCREENS_SIZES as $screen => $sizes) {
            foreach ($sizes as $screenSize) {
                if ($screenSize === $size || $screenSize === ($size / 2)) {
                    if ($this->generator && !$asReturn) {
                        $this->screen = $screen;
                    } elseif ($asReturn) {
                        return $screen;
                    }
                }
            }
        }

        return 'desktop';
    }

    /**
     * To get runtime configuration.
     */
    private function getRuntimeConfig(Media\Thumb $thumb, $size, $options): array
    {
        $isRetinaSize = in_array($size, self::RETINA_SIZES);
        if (is_int($thumb->getDataX()) && $thumb->getDataX() > 0 && is_int($thumb->getDataY()) && $thumb->getDataY() > 0) {
            $runtimeConfig['scale']['to'] = $thumb->getScale();
            $runtimeConfig['crop']['size'] = [$thumb->getWidth(), $thumb->getHeight()];
            $runtimeConfig['crop']['start'] = [$thumb->getDataX(), $thumb->getDataY()];
        } elseif (!$thumb->getWidth() && $thumb->getHeight() > 0) {
            $originalHeight = $options['sizeInfo']->getHeight();
            $retinaSize = $thumb->getHeight() * 2;
            $height = $isRetinaSize && $retinaSize <= $originalHeight ? $retinaSize : $thumb->getHeight();
            $runtimeConfig['relative_resize']['heighten'] = $height > $originalHeight ? $originalHeight : $height;
        } elseif ($thumb->getWidth() > 0 && !$thumb->getHeight()) {
            $originalWidth = $options['sizeInfo']->getWidth();
            $retinaSize = $thumb->getWidth() * 2;
            $width = $isRetinaSize && $retinaSize <= $originalWidth ? $retinaSize : $thumb->getWidth();
            $runtimeConfig['relative_resize']['widen'] = $width > $originalWidth ? $originalWidth : $width;
        } else {
            $runtimeConfig['upscale']['min'] = [$thumb->getWidth(), $thumb->getHeight()];
            $runtimeConfig['thumbnail']['size'] = [$thumb->getWidth(), $thumb->getHeight()];
            $runtimeConfig['thumbnail']['mode'] = 'outbound';
        }

        return $runtimeConfig;
    }

    /**
     * To generate a lazy file.
     */
    private function getLazy(array $thumbnails, int $currentSize, array $options): mixed
    {
        $file = !empty($thumbnails['files'][$currentSize]) ? $thumbnails['files'][$currentSize] : (!empty($thumbnails['files']) ? end($thumbnails['files']) : null);
        $thumbInfos = !empty($thumbnails['thumbs'][$currentSize]) ? $thumbnails['thumbs'][$currentSize] : (!empty($thumbnails['thumbs']) ? end($thumbnails['thumbs']) : null);
        $cropInfos = !empty($thumbnails['thumbs'][$currentSize]) ? $thumbnails['thumbs'][$currentSize]->cropInfos : null;
        $extension = is_object($cropInfos) ? $cropInfos->extension : null;
        $runtimeConfig = !empty($thumbnails['runtimeConfig'][$currentSize]) ? $thumbnails['runtimeConfig'][$currentSize] : null;
        $runtimeConfig['background']['transparency'] = 0;

        if ('svg' === $extension && self::LAZY_SVG_DATA) {
            return $file;
        } elseif (!self::LAZY_ORIGINAL) {
            $thumbInfos = (array) $thumbInfos;
            $thumbInfos['cropInfos'] = (array) $thumbInfos['cropInfos'];
            $thumbInfos['cropInfos']['dirname'] = $this->coreLocator->projectDir().'\public\medias\lazy-file.png';
            $thumbInfos['cropInfos']['extension'] = 'png';
            $thumbInfos['cropInfos'] = (object) $thumbInfos['cropInfos'];
            $thumbInfos = (object) $thumbInfos;
        }

        return $thumbInfos ? $this->getThumbnail($thumbInfos, $runtimeConfig, 'media1', $options) : null;
    }

    /**
     * To generate thumbnail.
     */
    public function getThumbnail(object $thumbInfos, array $runtimeConfig, ?string $filter = null, array $options = [], ?int $size = null): string
    {
        $dirname = $thumbInfos->cropInfos->dirname;
        $dirname = substr($dirname, 0, 1) !== ('/' || '\\') ? '/'.$dirname : $dirname;
        $publicDirname = $this->projectDirname.'/public';
        $dirname = str_replace([$publicDirname.'/', $this->projectDirname.'\public\\', '/public'], '', $dirname);
        $dirname = str_replace(['/', '\\', '%20', $this->schemeAndHttpHost, '//'], ['/', '/', ' ', '', '/'], $dirname);
        $media = property_exists($thumbInfos, 'media') ? $thumbInfos->media : null;
        $extension = $thumbInfos->cropInfos->extension;
        $quality = isset($options['filter']) && !empty($this->yamlConfig['liip_imagine']['filter_sets'][$options['filter']]['quality'])
            ? $this->yamlConfig['liip_imagine']['filter_sets'][$options['filter']]['quality'] : ($media ? $media->getQuality() : 100);

        if (in_array($extension, self::ALLOWED_EXTENSIONS)) {
            $imagineWebp = (self::ACTIVE_WEBP && $this->isWebpSupported() && 'webp' !== $extension) || self::ALWAYS_WEBP;
            $cacheDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->coreLocator->projectDir().'/public'.$dirname);
            $dimensions = $this->getImageDimensions($this->coreLocator->projectDir().'/public'.$dirname);
            $originalWidth = $dimensions['width'];
            $originalHeight = $dimensions['height'];
            $cropWidth = !empty($runtimeConfig['thumbnail']['size'][0]) ? $runtimeConfig['thumbnail']['size'][0] : null;
            $cropHeight = !empty($runtimeConfig['thumbnail']['size'][1]) ? $runtimeConfig['thumbnail']['size'][1] : null;
            $filter = 1 === $quality ? 'media1' : ($filter ?: (self::FORCE_QUALITY ? 'media100' : 'media'.$quality));
            $loaderFilename = $options['loaderFilename'] ?? null;
            if ('media1' !== $filter && $cropWidth === $originalWidth && $cropHeight === $originalHeight) {
                $copyDirname = $this->coreLocator->projectDir().'/public/thumbnails/originals'.$dirname;
                $copyDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $copyDirname);
                if (!$this->filesystem->exists($copyDirname)) {
                    $this->filesystem->copy($this->coreLocator->projectDir().'/public'.$dirname, $copyDirname);
                }
                $path = $this->schemeAndHttpHost.str_replace([$this->coreLocator->projectDir(), '\\public', '\\'], ['', '', '/'], $copyDirname);
            } else {
                $dirnameForPath = 'webp' === $extension ? $dirname : str_replace(['.webp', '.avif'], '', $dirname);
                try {
                    $path = $this->filterService->getUrlOfFilteredImageWithRuntimeFilters($dirnameForPath, $filter, $runtimeConfig);
                } catch (\Exception $e) {
                    return $this->publicPath($this->projectDirname.'/public/'.$dirname);
                }
            }
            if ($loaderFilename && 'media1' !== $filter && 1 !== $quality) {
                $prefix = isset($options['inAdmin']) && $options['inAdmin'] ? 'admin' : 'front';
                $dirnameGenerated = $this->coreLocator->projectDir().'/public/thumbnails/generated/';
                $dirnameGenerated = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirnameGenerated);
                $filesystem = new Filesystem();
                if (!$filesystem->exists($dirnameGenerated)) {
                    $filesystem->mkdir($dirnameGenerated);
                }
                $dirnameGenerated = $dirnameGenerated.$prefix.'-'.$media->getWebsite()->getUploadDirname().'.cache.json';
                $jsonData = $filesystem->exists($dirnameGenerated) ? (array) json_decode(file_get_contents($dirnameGenerated)) : [];
                if (!isset($jsonData[$loaderFilename]) && empty($options['noCache'])) {
                    $jsonData[$loaderFilename] = true;
                    $fp = fopen($dirnameGenerated, 'w');
                    fwrite($fp, json_encode($jsonData, JSON_PRETTY_PRINT));
                    fclose($fp);
                }
            }
            if ($imagineWebp || (!$this->isWebpSupported() && 'media1' === $filter)) {
                $dirname = str_replace($this->schemeAndHttpHost, '', $path);
                $copyDirname = !str_contains($dirname, '/public')
                    ? $this->coreLocator->projectDir().'/public/'.ltrim(str_replace(['.webp', '.avif'], '', $dirname), '/')
                    : $this->coreLocator->projectDir().'/'.ltrim(str_replace(['.webp', '.avif'], '', $dirname), '/');
                $copyDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $copyDirname);
                //                $validFile = 'png' !== $extension || $this->verifyPNGSignature($copyDirname) || str_contains($dirname, 'lazy-file.png');
                $newDirname = $imagineWebp ? $copyDirname.'.webp' : str_replace($media->getFilename(), str_replace('.'.$media->getExtension(), '-blur.'.$media->getExtension(), $media->getFilename()), $copyDirname);
                if ($this->isAvifSupported()) {
                    $newDirname = str_replace('.webp', '.avif', $newDirname);
                }
                $newPath = $this->schemeAndHttpHost.str_replace([$this->coreLocator->projectDir(), '\\public', '\\'], ['', '', '/'], $newDirname);
                if (($this->filesystem->exists($copyDirname) && !$this->filesystem->exists($newDirname))
                    || ($this->filesystem->exists($copyDirname) && 'media1' === $filter && !$this->filesystem->exists($newDirname))) {
                    try {
                        $img = 'png' === $extension ? @imagecreatefrompng($copyDirname) : @imagecreatefromjpeg($copyDirname);
                        $function = $imagineWebp ? 'imagewebp' : ('png' === $extension ? 'imagepng' : 'imagejpeg');
                        $quality = 'png' === $extension ? 101 : 100;
                        if ($this->avifSupport) {
                            $function = 'imageavif';
                            $quality = 72;
                        }
                        if ($img instanceof \GdImage) {
                            $imgWidth = imagesx($img);
                            $imgHeight = imagesy($img);
                            $image = imagecreatetruecolor($imgWidth, $imgHeight);
                            imagealphablending($image, false);
                            imagesavealpha($image, true);
                            $trans = imagecolorallocatealpha($image, 0, 0, 0, 127);
                            imagefilledrectangle($image, 0, 0, $imgWidth - 1, $imgHeight - 1, $trans);
                            imagecopy($image, $img, 0, 0, 0, 0, $imgWidth, $imgHeight);
                            if ('media1' === $filter) {
                                for ($i = 0; $i < 20; ++$i) {
                                    imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
                                    imagefilter($image, IMG_FILTER_SMOOTH, 10); // Ajoute un effet lissant
                                }
                                $function($image, $newDirname, 'png' === $extension ? 0 : 50);
                            } else {
                                $function($image, $newDirname, $quality);
                            }
                            imagedestroy($image);
                            $path = $newPath;
                        }
                    } catch (\Exception $e) {
                    }
                } elseif ($this->filesystem->exists($newDirname)) {
                    $path = $newPath;
                }
            }
        } else {
            $path = $this->schemeAndHttpHost.str_replace('\\', '/', $dirname);
        }

        return str_replace(['//thumbnails', '/public'], ['/thumbnails', ''], $path);
    }

    /**
     * To get screen media.
     */
    private function getScreenMedia(string $screen, Media\Media $media): ?Media\Media
    {
        if ($media->isHaveMediaScreens()) {
            foreach ($media->getMediaScreens() as $mediaScreen) {
                if ($mediaScreen->getFilename() && $screen === $mediaScreen->getScreen()) {
                    return $mediaScreen;
                }
            }
        }

        if ($screen !== $media->getScreen()) {
            $mediaScreen = new Media\Media();
            $mediaScreen->setScreen($screen);
            $mediaScreen->setFilename($media->getFilename());
            $mediaScreen->setExtension($media->getExtension());
            $mediaScreen->setQuality($media->getQuality());
            $mediaScreen->setWebsite($media->getWebsite());
            foreach ($media->getThumbs() as $thumb) {
                $mediaScreen->addThumb($thumb);
            }

            return $mediaScreen;
        }

        return $media;
    }

    /**
     * To set thumbnails infos.
     */
    private function infos(
        FileInfo $fileInfo,
        array $thumbnails,
        Media\Media $media,
        array $runtimeConfig = [],
        ?MediaModel $mediaModel = null,
        ?IntlModel $mediaIntl = null,
        ?IntlModel $mediaRelationIntl = null,
        array $options = [],
    ): array {
        $mergedExtensions = array_merge(self::ALLOWED_EXTENSIONS, self::EXCEPTIONS_EXTENSIONS);
        $extensionsPattern = implode('|', array_map('preg_quote', $mergedExtensions));
        $haveMediaRelationIntl = $mediaRelationIntl instanceof IntlModel;
        $haveMediaIntl = $mediaIntl instanceof IntlModel;
        $mediaRelationTitle = $haveMediaRelationIntl && $mediaRelationIntl->placeholder ? $mediaRelationIntl->placeholder
            : ($haveMediaRelationIntl && $mediaRelationIntl->title ? $mediaRelationIntl->title : null);
        $intlTitle = $mediaRelationTitle ?: ($haveMediaIntl && $mediaIntl->placeholder ? $mediaIntl->placeholder : null);
//        if ((!$mediaRelationTitle && $intlTitle) || ($mediaRelationTitle && !$mediaRelationIntl->placeholder)) {
//            $intlTitle = 'Image '.$intlTitle;
//        }
        $title = !$intlTitle && !empty($options['title']) ? $options['title'] : (!$intlTitle && $fileInfo->getFilename() ? $fileInfo->getFilename() : (!$intlTitle ? $media->getName() : null));
        $title = $intlTitle ?: ($title ? str_replace('-', ' ', ucfirst(preg_replace('/\.('.$extensionsPattern.')$/i', '', $title))) : null);
        $svgSizes = !empty($this->cache[$fileInfo->getDirname()]['svgSizes']) ? $this->cache[$fileInfo->getDirname()]['svgSizes'] : null;

        $thumbnails['infos']['intlTitle'] = $haveMediaRelationIntl ? $mediaRelationIntl->title : null;
        $thumbnails['infos']['alt'] = $title ? preg_replace('/\.('.$extensionsPattern.')$/i', '', $title) : null;
        $thumbnails['infos']['author'] = $mediaModel?->copyright;
        $thumbnails['infos']['copyright'] = $mediaModel?->copyright;
        $thumbnails['infos']['notContractual'] = $media->isNotContractual();
        $thumbnails['infos']['newTab'] = $haveMediaRelationIntl ? $mediaRelationIntl->linkBlank : false;
        $thumbnails['infos']['extension'] = $media->getExtension();
        $thumbnails['infos']['filename'] = $media->getFilename();
        $thumbnails['infos']['asDecor'] = $options['decor'] ?? 'svg' === $thumbnails['infos']['extension'];

        $thumbnails['infos']['width'] = !empty($runtimeConfig['thumbnail']['size'][0]) ? $runtimeConfig['thumbnail']['size'][0] : (!empty($svgSizes['width']) ? $svgSizes['width'] : null);
        $thumbnails['infos']['height'] = !empty($runtimeConfig['thumbnail']['size'][1]) ? $runtimeConfig['thumbnail']['size'][1] : (!empty($svgSizes['height']) ? $svgSizes['height'] : null);
        if (!$thumbnails['infos']['width'] && !$thumbnails['infos']['height'] && !empty($runtimeConfig['relative_resize']['heighten'])) {
            $thumbnails['infos']['height'] = $runtimeConfig['relative_resize']['heighten'];
            $thumbnails['infos']['width'] = (int) ceil(($thumbnails['infos']['height'] * $options['sizeInfo']->getWidth()) / $options['sizeInfo']->getHeight());
        } elseif (!$thumbnails['infos']['width'] && !$thumbnails['infos']['height'] && !empty($runtimeConfig['relative_resize']['widen'])) {
            $thumbnails['infos']['width'] = $runtimeConfig['relative_resize']['widen'];
            $thumbnails['infos']['height'] = (int) ceil(($thumbnails['infos']['width'] * $options['sizeInfo']->getHeight()) / $options['sizeInfo']->getWidth());
        }

        return $thumbnails;
    }

    /**
     * To set thumbnails classes.
     */
    private function attributes(mixed $mediaRelation, array $thumbnails, int $currentSize, array $options): array
    {
        $infos = !empty($thumbnails['infos']) ? $thumbnails['infos'] : null;

        if (empty($infos)) {
            return [];
        }

        $lazyLoad = $options['lazyLoad'] ?? true;
        $asDecor = isset($options['decor']) && $options['decor'] || !isset($options['decor']) && !empty($options['originalSrc']) && str_contains($options['originalSrc'], '.svg');
        $noFluid = $options['noFluid'] ?? false;
        $thumb = !empty($thumbnails['thumbs'][$currentSize]) ? $thumbnails['thumbs'][$currentSize]->thumb : (!empty($thumbnails['thumbs']) ? end($thumbnails['thumbs'])->thumb : null);

        $class = !$noFluid ? 'img-fluid img-'.$infos['extension'] : 'img-'.$infos['extension'];
        $class .= !empty($options['class']) ? ' '.$options['class'] : '';
        $class .= $lazyLoad ? ' lazy-load ' : '';
        if ($mediaRelation && $mediaRelation->getId() && $mediaRelation->isRadius()) {
            $class .= ' radius';
        }

        $attributes = in_array($infos['extension'], self::ALLOWED_EXTENSIONS) || 'webp' === $infos['extension'] ? '' : ($thumb->getWidth() ? 'width="'.$thumb->getWidth().'"' : '');
        $attributes .= in_array($infos['extension'], self::ALLOWED_EXTENSIONS) || 'webp' === $infos['extension'] ? '' : ($thumb->getHeight() ? ' height="'.$thumb->getHeight().'"' : '');
        $attributes .= $class ? ' class="'.ltrim($class).'"' : '';
        $attributes .= $asDecor ? ' alt=""' : ($infos['alt'] ? ' alt="'.trim(strip_tags($infos['alt'])).'"' : ' alt="'.$infos['filename'].'"');
        $attributes .= $asDecor ? ' role="presentation"' : '';
        if (!empty($options['data'])) {
            foreach ($options['data'] as $key => $value) {
                $attributes .= ' data-'.$key.'="'.$value.'"';
            }
        }
        if (!empty($options['id'])) {
            $attributes .= ' id="'.$options['id'].'"';
        }
        $thumbnails['infos']['attr'] = $attributes;

        return $thumbnails;
    }

    /**
     * Get svg sizes.
     */
    private function svgSizes(Media\Media $media, string $dirname, ?int $width, ?int $height): array
    {
        if (isset($this->cache[$dirname]['svgSizes'])) {
            return $this->cache[$dirname]['svgSizes'];
        }

        if ('svg' === $media->getExtension() && $this->filesystem->exists($dirname)) {
            $svgWidth = null;
            $svgHeight = null;
            $svg = file_get_contents($dirname);
            if (preg_match('/viewBox="([^"]*)"/', $svg, $matches)) {
                $viewBox = $matches[1];
                $vbParts = explode(' ', $viewBox);
                $svgWidth = isset($vbParts[2]) && (float)$vbParts[2] > 0 ? (float)$vbParts[2] : null;
                $svgHeight = isset($vbParts[3]) && (float)$vbParts[3] > 0 ? (float)$vbParts[3] : null;
            }
            if (!$svgWidth && !$svgHeight) {
                if (preg_match('/width="([^"]*)"/', $svg, $matches)) {
                    $svgWidth = (float)$matches[1];
                }
                if (preg_match('/height="([^"]*)"/', $svg, $matches)) {
                    $svgHeight = (float)$matches[1];
                }
            }
            if ($svgWidth && $svgHeight) {
                $width = !$width && $height ? (int) ceil(($svgWidth * $height) / $svgHeight) : (int) ceil($svgWidth);
                if (!$height) {
                    $height = $width ? (int) ceil(($svgHeight * $width) / $svgWidth) : (int) ceil($svgHeight);
                }
            }
        }

        return $this->cache[$dirname]['svgSizes'] = [
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * To set a large file.
     */
    private function largeFile(array $thumbnails, FileInfo $fileInfo): array
    {
        $thumbnails['largeFilename'] = $fileInfo->getFilename();
        $thumbnails['largeFileSize'] = $fileInfo->getSize() > 500000 ? $fileInfo->getSize() : null;
        $thumbnails['largeFileWidth'] = $fileInfo->getWidth() > self::MAX_FILE_WIDTH ? $fileInfo->getWidth() : null;
        $thumbnails['largeFileHeight'] = $fileInfo->getHeight() > self::MAX_FILE_HEIGHT ? $fileInfo->getHeight() : null;
        $thumbnails['maxSizeLimit'] = self::MAX_FILE_SIZE_OPTIMIZATION;
        $thumbnails['maxWidthLimit'] = self::MAX_FILE_WIDTH;
        $thumbnails['maxHeightLimit'] = self::MAX_FILE_HEIGHT;
        $thumbnails['maxWidth'] = $fileInfo->getWidth();
        $thumbnails['maxHeight'] = $fileInfo->getHeight();

        return $thumbnails;
    }

    /**
     * To set dirname.
     */
    private function dirname(?string $dirname = null): ?string
    {
        if ($dirname) {
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        }

        return $dirname;
    }

    /**
     * To set dirname.
     */
    private function publicPath(?string $path = null): ?string
    {
        if ($path) {
            $path = str_replace([$this->projectDirname, DIRECTORY_SEPARATOR], ['', '/'], $path);
            $path = str_replace(['/public'], [''], $path);
            if (!str_contains($path, $this->schemeAndHttpHost)) {
                $path = $this->schemeAndHttpHost.'/'.ltrim($path, '/');
            }
        }

        return $path;
    }

    /**
     * To check png signature.
     */
    private function verifyPNGSignature(string $filePath): bool
    {
        $pngSignature = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
        $fileHandle = fopen($filePath, 'rb');
        if (!$fileHandle) {
            return false;
        }
        if (1 == filesize($filePath) % 2) {
            return false;
        }
        $header = fread($fileHandle, 8);
        fclose($fileHandle);

        return $header === $pngSignature;
    }

    /**
     * Get Media sizes.
     */
    private function mediaSizes(int $size, mixed $mediaRelation = null, array $options = []): array
    {
        $screen = null;
        foreach (self::SCREENS_SIZES as $screen => $sizes) {
            if (in_array($size, $sizes)) {
                break;
            }
        }

        $width = !empty($options['maxWidth']) ? $options['maxWidth'] : (!empty($options['width']) ? $options['width'] : null);
        $height = !empty($options['maxHeight']) ? $options['maxHeight'] : (!empty($options['height']) ? $options['height'] : null);

        if ($options['asMediaRelation']) {
            $width = $mediaRelation->getMaxWidth() ?: $width;
            $height = $mediaRelation->getMaxHeight() ?: $height;
            $screenWidthMethod = 'desktop' === $screen ? 'getMaxWidth' : 'get'.ucfirst($screen).'MaxWidth';
            $screenWidth = method_exists($mediaRelation, $screenWidthMethod) ? $mediaRelation->$screenWidthMethod() : null;
            $screenHeightMethod = 'desktop' === $screen ? 'getMaxHeight' : 'get'.ucfirst($screen).'MaxHeight';
            $screenHeight = method_exists($mediaRelation, $screenHeightMethod) ? $mediaRelation->$screenHeightMethod() : null;
            $width = $screenWidth || $screenHeight ? $screenWidth : $width;
            $height = $screenHeight || $screenWidth ? $screenHeight : $height;
        }

        if (!empty($options['screensSizes'])) {
            foreach ($options['screensSizes'] as $screen => $sizes) {
                if (in_array($size, self::SCREENS_SIZES[$screen])) {
                    $width = !empty($sizes['width']) ? $sizes['width'] : $width;
                    $height = !empty($sizes['height']) ? $sizes['height'] : $height;
                }
            }
        }

        return [
            'maxWidth' => $width,
            'maxHeight' => $height,
        ];
    }

    /**
     * To get Screen sizes.
     */
    private function getScreenSizes(): void
    {
        $this->screensSizes = array_merge(self::SIZES, self::RETINA_SIZES);
        if ($this->inAdmin) {
            $desktopSizes = array_values(self::SCREENS_SIZES['desktop']);
            $this->screensSizes = [array_shift($desktopSizes)];
            sort($this->screensSizes);
        }
    }

    /**
     * To get Yaml sizes.
     */
    private function getYamlSizes(string $filter): array
    {
        $filter = !empty($this->yamlConfig['liip_imagine']['filter_sets'][$filter]['filters']['upscale']['min'])
            ? $this->yamlConfig['liip_imagine']['filter_sets'][$filter]['filters']['upscale']['min'] : null;

        return [
            'width' => !empty($filter[0]) ? $filter[0] : null,
            'height' => !empty($filter[1]) ? $filter[1] : null,
        ];
    }

    /**
     * To get SIZES.
     */
    public function getSizes(): array
    {
        return self::SIZES;
    }

    /**
     * To get RETINA_SIZES.
     */
    public function getRetinaSizes(): array
    {
        return self::RETINA_SIZES;
    }

    /**
     * To get MAX_FILE_SIZE.
     */
    public function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /**
     * To get MAX_FILE_WIDTH.
     */
    public function getMaxFileWidth(): int
    {
        return self::MAX_FILE_WIDTH;
    }

    /**
     * To get MAX_FILE_HEIGHT.
     */
    public function getMaxFileHeight(): int
    {
        return self::MAX_FILE_HEIGHT;
    }

    /**
     * To get ALLOWED_EXTENSIONS.
     */
    public function getAllowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    /**
     * To get EXCEPTIONS_EXTENSIONS.
     */
    public function getExceptionsExtensions(): array
    {
        return self::EXCEPTIONS_EXTENSIONS;
    }
}
