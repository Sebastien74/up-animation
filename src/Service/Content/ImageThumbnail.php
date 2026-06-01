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
use DateMalformedStringException;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
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
    private const int MAX_FILE_WIDTH = 3840; // pixels 3840screensSizes
    private const int MAX_FILE_HEIGHT = 6000; // pixels 6000
    private const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const array EXCEPTIONS_EXTENSIONS = ['svg', 'gif', 'tiff', 'raw', 'heic'];
    private const string SVG_RESIZED_DIR = 'thumbnails/svg-resized';
    private const array CONTAINER_SIZE = [
        1200 => 869,
        1400 => 1013,
        1920 => 1391,
    ];
    // Bootstrap 5 breakpoints: xs/sm/md < 992 → mobile, lg → tablet, xl → laptop,
    // xxl + 4K → desktop. 768 stays in mobile because PageSpeed audits it as mobile.
    private const array SIZES = [480, 768, 992, 1200, 1400, 1920];
    private const array RETINA_SIZES = [960, 1536, 1984, 2400, 2800, 3840];
    private const array SCREENS_SIZES = [
        'mobile' => [480, 960, 768, 1536],
        'tablet' => [992, 1984],
        'laptop' => [1200, 2400],
        'desktop' => [1400, 2800, 1920, 3840],
    ];
    private const array SCREENS_SIZES_ATTR = [
        'mobile' => 480,
        'tablet' => 992,
        'laptop' => 1200,
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

        $session?->set('WEBP_SUPPORT', $this->webpSupport);

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
     *
     * @throws DateMalformedStringException
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
        $originalDirname = $options['originalSrc'] = $fileDirname ?: ((str_contains($media->getOriginalName(), '/build/') || str_contains($media->getOriginalName(), '/medias/'))
            ? $this->dirname($this->projectDirname.'/public'.$media->getOriginalName()) : $this->dirname($this->projectDirname.'/public/uploads/'.$this->uploadDirname.'/'.$media->getOriginalName()));
        $originalExist = $this->filesystem->exists($originalDirname);
        $originalInfoFile = $media->getOriginalName() ? $this->coreLocator->fileInfo()->file($website, $media->getOriginalName(), $originalDirname) : null;
            $isEnableMaxSizes = $originalInfoFile && $originalInfoFile->getWidth() <= self::MAX_FILE_WIDTH && $originalInfoFile->getHeight() <= self::MAX_FILE_HEIGHT && $originalInfoFile->getSize() <= self::MAX_FILE_SIZE;
            $mediaRelation = $options['mediaRelation'] = !empty($options['mediaRelation']) ? $options['mediaRelation'] : ($asMediaModel ? $mediaModel->mediaRelation : null);
            $execute = $infoFile = false;

            $loaderFilename = $options['loaderFilename'] ?? null;
            if ($loaderFilename && !$generator) {
                $prefix = $this->inAdmin ? 'admin' : 'front';
                $cacheFile = $this->projectDirname . '/public/thumbnails/generated/' . $prefix . '-' . $this->uploadDirname . '.cache.json';
                $cacheFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cacheFile);
                if (!isset($this->cache['json_data']) && $this->filesystem->exists($cacheFile)) {
                    $this->cache['json_data'] = (array)json_decode(file_get_contents($cacheFile));
                }
            }

        if ($media instanceof Media\Media && $media->getOriginalName()) {
            $extension = $this->getExtension($media);
            if ($extension && ('desktop' === $media->getScreen() || 'poster' === $media->getScreen())) {
                $publicDir = $this->projectDirname.'/public';
                $placeholderBack = $this->dirname($publicDir.'/medias/placeholder-back.jpg');
                $placeholderDefault = $this->dirname($publicDir.'/medias/placeholder.jpg');
                foreach ($this->screensSizes as $size) {
                    $screen = $this->screen($size, true);
                    $screenMedia = $asMediaModel && !$this->inAdmin ? $this->getScreenMedia($screen, $media) : $media;
                    $filename = $screenMedia->getOriginalName();
                    $dirname = $fileDirname ?: ((str_contains($filename, '/build/') || str_contains($filename, '/medias/'))
                        ? $this->dirname($publicDir.$filename) : $this->dirname($publicDir.'/uploads/'.$this->uploadDirname.'/'.$filename));
                    if (!$this->filesystem->exists($dirname)) {
                        $dirname = $this->inAdmin ? $placeholderBack : $placeholderDefault;
                    }
                    $asLoader = $options['loader'] ?? false;
                    $isEnableSize = in_array($size, self::SIZES) || in_array($size, self::RETINA_SIZES);
                    $isEnableMedia = !$mediaRelation || !$mediaRelation->getId() || ($mediaRelation->getCacheDate() instanceof \DateTime && !$asLoader);
                    $isEnableEnv = $generator || ($this->inAdmin && !isset($options['loader'])) || ($options['forceThumb'] ?? false);
                    $infoFile = $options['sizeInfo'] = $this->coreLocator->fileInfo()->file($website, $media->getOriginalName(), $dirname);
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
                    if ($loaderFilename && isset($this->cache['json_data'][$loaderFilename])) {
                        $execute = false;
                    }
                    if ('svg' === $extension) {
                        $thumbnails = $this->buildSvgThumbnail($thumbnails, $screen, $size, $options, $screenMedia, $dirname);
                        continue;
                    }
                    try {
                        $thumb = $this->getScreenThumb($screenMedia, $mediaRelation, $thumbs, $screen, $dirname, $size, $options);
                        $thumb = $mediaRelation ? $this->setRatio($mediaRelation, $thumb, $size, $options) : $thumb;
                        $runtimeConfig = $this->getRuntimeConfig($thumb->thumb, $size, $options);
                        $thumbnails['runtimeConfig'][$size] = $runtimeConfig;
                        $thumbnails['thumbs'][$size] = $thumb;
                        if ($execute || ($loaderFilename && isset($this->cache['json_data'][$loaderFilename]))) {
                            $thumbnails['files'][$size] = $this->publicPath($this->getThumbnail($thumb, $runtimeConfig, null, $options, $size));
                        } else {
                            $thumbnails['files'][$size] = $this->publicPath($this->getThumbnail($thumb, $runtimeConfig, null, array_merge($options, ['noCache' => true]), $size));
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

        if ((!$isEnableMaxSizes && $media->getOriginalName()) || ($infoFile && self::MAX_FILE_SIZE_OPTIMIZATION < $infoFile->getSize())) {
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
        $thumbnails['originalSrc'] = $originalExist && !$file instanceof File ? '/uploads/'.$this->uploadDirname.'/'.$media->getOriginalName()
            : ('cms-component' === $media->getCategory() ? $media->getOriginalName() : ($file instanceof File ? str_replace([$this->projectDirname, '\\', '/public'], ['', '/', ''], $fileDirname) : '/medias/placeholder.jpg'));
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
        $thumbnails = $media->getOriginalName() ? $this->infos($originalInfoFile, $thumbnails, $media, $currentRuntime, $mediaModel, $mediaIntl, $mediaRelationIntl, $options) : $thumbnails;
        $info = !empty($thumbnails['infos']) ? $thumbnails['infos'] : null;

        //        if (isset($options['loader']) || self::LAZY_SVG_DATA && $mediaModel && 'svg' === $mediaModel->extension) {
        $thumbnails['lazyFileSvg'] = 'data:image/svg+xml,%3Csvg width="'.$info['width'].'" height="'.$info['height'].'" xmlns="http://www.w3.org/2000/svg"%3E%3Crect x="0" y="0" width="'.$info['width'].'" height="'.$info['height'].'" fill="none"/%3E%3C/svg%3E';
        //        }

        if (!$this->inAdmin && !isset($options['filter']) && $mediaRelation && $execute && !$mediaRelation->getCacheDate() instanceof \DateTime && $mediaRelation->getId()) {
            $mediaRelation = $this->coreLocator->em()->getRepository(get_class($mediaRelation))->find($mediaRelation->getId());
            $mediaRelation->setCacheDate(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $this->coreLocator->em()->persist($mediaRelation);
            $this->coreLocator->em()->flush();
        }

        $this->preload($thumbnails, $options);

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
        if (!$thumbConfiguration && !empty($thumbs)) {
            foreach (['desktop', 'laptop', 'tablet', 'mobile'] as $screenKey) {
                if (!empty($thumbs[$screenKey])) {
                    $thumbConfiguration = $thumbs[$screenKey];
                    break;
                }
            }
        }

        $isRetinaSize = in_array($size, self::RETINA_SIZES);
        $retinaSet = false;

        $optionsScreensSizes = !empty($options['screensSizes']) ? $options['screensSizes'] : [];
        $optionMaxWidth = !empty($options['maxWidth']) ? intval($options['maxWidth']) : (!empty($options['width']) ? intval($options['width']) : null);
        $optionMaxHeight = !empty($options['maxHeight']) ? intval($options['maxHeight']) : (!empty($options['height']) ? intval($options['height']) : null);

        $width = null;
        $height = null;

        // 0. ABSOLUTE PRIORITY: back-office MediaThumb overrides all other sources
        // (MediaRelation, screensSizes options, width/height).
        $mediaThumbForScreen = null;

        // 0a. Strict match by ThumbConfiguration ID - guards against picking the wrong
        // MediaThumb when several mobile entries coexist (residual configs).
        if ($thumbConfiguration instanceof Media\ThumbConfiguration && $thumbConfiguration->getId()) {
            foreach ($media->getThumbs() as $mediaThumb) {
                $mediaThumbConfig = $mediaThumb->getConfiguration();
                if ($mediaThumbConfig
                    && $mediaThumbConfig->getId() === $thumbConfiguration->getId()
                    && ($mediaThumb->getWidth() > 0 || $mediaThumb->getHeight() > 0)) {
                    $mediaThumbForScreen = $mediaThumb;
                    $width = $mediaThumb->getWidth();
                    $height = $mediaThumb->getHeight();
                    break;
                }
            }
        }

        // 0b. Screen fallback chain - a single "mobile" MediaThumb applies to all larger
        // screens; mandatory when only one screen size is defined in DB.
        if (!$mediaThumbForScreen) {
            $fallbackScreens = match ($screen) {
                'desktop' => ['desktop', 'laptop', 'tablet', 'mobile'],
                'laptop' => ['laptop', 'tablet', 'mobile'],
                'tablet' => ['tablet', 'mobile'],
                'mobile' => ['mobile'],
                default => [$screen],
            };
            foreach ($fallbackScreens as $tryScreen) {
                foreach ($media->getThumbs() as $mediaThumb) {
                    $mediaThumbConfig = $mediaThumb->getConfiguration();
                    if ($mediaThumbConfig
                        && $mediaThumbConfig->getScreen() === $tryScreen
                        && ($mediaThumb->getWidth() > 0 || $mediaThumb->getHeight() > 0)) {
                        $mediaThumbForScreen = $mediaThumb;
                        $thumbConfiguration = $mediaThumbConfig;
                        $width = $mediaThumb->getWidth();
                        $height = $mediaThumb->getHeight();
                        break 2;
                    }
                }
            }
        }

        // 1. Priority: MediaRelation
        if (!$mediaThumbForScreen && $mediaRelation) {
            $methodWidth = 'desktop' === $screen ? 'getMaxWidth' : 'get'.ucfirst($screen).'MaxWidth';
            $methodHeight = 'desktop' === $screen ? 'getMaxHeight' : 'get'.ucfirst($screen).'MaxHeight';
            $width = method_exists($mediaRelation, $methodWidth) ? $mediaRelation->$methodWidth() : null;
            $height = method_exists($mediaRelation, $methodHeight) ? $mediaRelation->$methodHeight() : null;
            if (!$width && !$height) {
                $width = method_exists($mediaRelation, 'getMaxWidth') ? $mediaRelation->getMaxWidth() : null;
                $height = method_exists($mediaRelation, 'getMaxHeight') ? $mediaRelation->getMaxHeight() : null;
            }
        }

        // 2. Priority: screensSizes (passed in options)
        if ((!$width && !$height) && !empty($optionsScreensSizes)) {
            foreach ($optionsScreensSizes as $optScreen => $sizes) {
                if (in_array($size, self::SCREENS_SIZES[$optScreen])) {
                    $width = !empty($sizes['width']) ? intval($sizes['width']) : null;
                    $height = !empty($sizes['height']) ? intval($sizes['height']) : null;
                    break;
                }
            }
        }

        // 3. Priority: width and height (passed in options)
        if (!$width && !$height) {
            $width = $optionMaxWidth;
            $height = $optionMaxHeight;
        }

        // 4. Priority: ThumbConfiguration
        if ((!$width && !$height) && $thumbConfiguration instanceof Media\ThumbConfiguration) {
            $width = $thumbConfiguration->getWidth();
            $height = $thumbConfiguration->getHeight();
        }

        // Retina adjustment for options/relation sizes
        if ($isRetinaSize && ($width || $height)) {
            $width = $width ? (int) ceil($width * 2) : null;
            $height = $height ? (int) ceil($height * 2) : null;
            $retinaSet = true;
        }

        // Si on n'a toujours pas de taille définie, utiliser les tailles par défaut par écran
        if (!$width && !$height) {
            $width = self::SCREENS_SIZES_ATTR[$screen];
            // Pour mobile et tablet, on laisse la hauteur auto si non définie ?
            // L'utilisateur a fourni des constantes SCREENS_SIZES_ATTR qui ne contiennent que la largeur.
        }

        if (!$thumbConfiguration && ($width || $height)) {
            $thumbConfiguration = new Media\ThumbConfiguration();
            $thumbConfiguration->setWidth($width);
            $thumbConfiguration->setHeight($height);
            $thumbConfiguration->setScreen($screen);
        }

        if ($thumbConfiguration instanceof Media\ThumbConfiguration) {
            foreach ($media->getThumbs() as $mediaThumb) {
                if ($mediaThumb->getConfiguration()->getId() === $thumbConfiguration->getId() && ($mediaThumb->getWidth() > 0 || $mediaThumb->getHeight() > 0)) {
                    $thumbInfo = $this->setThumbInfos($media, $screen, $dirname, $size, $width, $height, $options, $thumbConfiguration);
                    $thumb = $thumbInfo->thumb;
                    $mediaThumbWidth = $mediaThumb->getWidth();
                    $mediaThumbHeight = $mediaThumb->getHeight();
                    $dataX = $isRetinaSize && is_numeric($mediaThumb->getDataX()) ? $mediaThumb->getDataX() * 2 : $mediaThumb->getDataX();
                    $dataY = $isRetinaSize && is_numeric($mediaThumb->getDataY()) ? $mediaThumb->getDataY() * 2 : $mediaThumb->getDataY();
                    $scale = $isRetinaSize ? 2 : 1;

                    if ($mediaThumb->getWidth() > $size) {
                        $mediaThumbWidth = $size;
                        $mediaThumbHeight = (int) ceil(($mediaThumb->getHeight() * $mediaThumbWidth) / $mediaThumb->getWidth());
                        $dataX = (int) ceil(($mediaThumbWidth * $mediaThumb->getDataX()) / $mediaThumb->getWidth());
                        $dataY = (int) ceil(($mediaThumbHeight * $mediaThumb->getDataY()) / $mediaThumb->getHeight());
                        $scale = $size / $mediaThumb->getWidth();
                    }

                    $thumbConfiguration = $thumb->getConfiguration();
                    $thumb->setWidth($mediaThumbWidth);
                    $thumb->setHeight($mediaThumbHeight);
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

        if (!$width && !$height) {
            $width = self::SCREENS_SIZES_ATTR[$screen] ?? 1920;
        }

        if (!$thumbConfiguration && ($width || $height)) {
            $thumbConfiguration = new Media\ThumbConfiguration();
            $thumbConfiguration->setWidth($width);
            $thumbConfiguration->setHeight($height);
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
            $newThumb->setScreen($screen);
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
        if ($width && $height && !$asCrop && $initCropWidth && $colSize && $colSize < 12 && (in_array($size, self::SCREENS_SIZES['desktop']) || in_array($size, self::SCREENS_SIZES['laptop']))) {
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

        $filename = $media->getOriginalName();
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
                if ($screenSize === $size || (in_array($size, self::RETINA_SIZES) && $screenSize === ($size / 2))) {
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
        $runtimeConfig = [];
        $isRetinaSize = in_array($size, self::RETINA_SIZES);
        if (is_int($thumb->getDataX()) && $thumb->getDataX() > 0 && is_int($thumb->getDataY()) && $thumb->getDataY() > 0) {
            $scaleFactor = is_numeric($thumb->getScale()) ? (float) $thumb->getScale() : 1.0;
            $runtimeConfig['scale']['to'] = $thumb->getScale();
            $runtimeConfig['crop']['size'] = [$thumb->getWidth(), $thumb->getHeight()];
            $runtimeConfig['crop']['start'] = [$thumb->getDataX(), $thumb->getDataY()];
            // Force final output to Thumb DB size (× scale for retina). Without this,
            // crop+scale alone can yield different dimensions depending on Liip mode.
            $runtimeConfig['thumbnail']['size'] = [
                (int) ceil($thumb->getWidth() * $scaleFactor),
                (int) ceil($thumb->getHeight() * $scaleFactor),
            ];
            $runtimeConfig['thumbnail']['mode'] = 'outbound';
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
            $filter = 1 === $quality ? 'media1' : ($filter ?: (self::FORCE_QUALITY ? 'media100' : 'media'.$quality));

            // Native webp shortcut disabled: it returned the cached file at the Liip
            // path without runtime hash, shared across all srcset variants. We now always
            // route through getUrlOfFilteredImageWithRuntimeFilters which segregates the
            // cache per runtimeConfig hash (rc/{hash}/).

            $cacheDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->coreLocator->projectDir().'/public'.$dirname);
            $dimensions = $this->getImageDimensions($this->coreLocator->projectDir().'/public'.$dirname);
            $originalWidth = $dimensions['width'];
            $originalHeight = $dimensions['height'];
            $cropWidth = !empty($runtimeConfig['thumbnail']['size'][0]) ? $runtimeConfig['thumbnail']['size'][0] : null;
            $cropHeight = !empty($runtimeConfig['thumbnail']['size'][1]) ? $runtimeConfig['thumbnail']['size'][1] : null;
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
                if (!isset($this->cache['json_data'])) {
                    $this->cache['json_data'] = $filesystem->exists($dirnameGenerated) ? (array) json_decode(file_get_contents($dirnameGenerated)) : [];
                }
                if (!isset($this->cache['json_data'][$loaderFilename]) && empty($options['noCache'])) {
                    $this->cache['json_data'][$loaderFilename] = true;
                    $fp = fopen($dirnameGenerated, 'w');
                    fwrite($fp, json_encode($this->cache['json_data'], JSON_PRETTY_PRINT));
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
                $newDirname = $imagineWebp ? $copyDirname.'.webp' : str_replace($media->getOriginalName(), str_replace('.'.$media->getExtension(), '-blur.'.$media->getExtension(), $media->getOriginalName()), $copyDirname);
                if ($this->isAvifSupported()) {
                    $newDirname = str_replace('.webp', '.avif', $newDirname);
                }
                $newPath = $this->schemeAndHttpHost.str_replace([$this->coreLocator->projectDir(), '\\public', '\\'], ['', '', '/'], $newDirname);

                if ($this->filesystem->exists($newDirname)) {
                    return $newPath;
                }

                if ($this->filesystem->exists($copyDirname)) {
                    try {
                        $img = 'png' === $extension ? @imagecreatefrompng($copyDirname) : @imagecreatefromjpeg($copyDirname);
                        $function = $imagineWebp ? 'imagewebp' : ('png' === $extension ? 'imagepng' : 'imagejpeg');
                        $mediaQuality = $media ? (int) $media->getQuality() : 0;
                        $mediaQuality = $mediaQuality > 0 ? max(1, min(100, $mediaQuality)) : 0;
                        if ($imagineWebp) {
                            $quality = $mediaQuality > 0 ? $mediaQuality : 80;
                        } elseif ('png' === $extension) {
                            $quality = 9;
                        } else {
                            $quality = $mediaQuality > 0 ? $mediaQuality : 85;
                        }
                        if ($this->avifSupport) {
                            $function = 'imageavif';
                            $quality = $mediaQuality > 0 ? (int) round($mediaQuality * 0.85) : 70;
                        }
                        if ($img instanceof \GdImage) {
                            $imgWidth = imagesx($img);
                            $imgHeight = imagesy($img);
                            // Target output size: when runtimeConfig.thumbnail.size is set
                            // (Thumb DB case), GD resizes to enforce strict dimensions.
                            // The 'media1' filter (lazy blur) keeps the source size.
                            $expectedWidth = ('media1' !== $filter && isset($runtimeConfig['thumbnail']['size'][0]))
                                ? (int) $runtimeConfig['thumbnail']['size'][0] : $imgWidth;
                            $expectedHeight = ('media1' !== $filter && isset($runtimeConfig['thumbnail']['size'][1]))
                                ? (int) $runtimeConfig['thumbnail']['size'][1] : $imgHeight;
                            $image = imagecreatetruecolor($expectedWidth, $expectedHeight);
                            imagealphablending($image, false);
                            imagesavealpha($image, true);
                            $trans = imagecolorallocatealpha($image, 0, 0, 0, 127);
                            imagefilledrectangle($image, 0, 0, $expectedWidth - 1, $expectedHeight - 1, $trans);
                            if ($expectedWidth === $imgWidth && $expectedHeight === $imgHeight) {
                                imagecopy($image, $img, 0, 0, 0, 0, $imgWidth, $imgHeight);
                            } else {
                                imagecopyresampled($image, $img, 0, 0, 0, 0, $expectedWidth, $expectedHeight, $imgWidth, $imgHeight);
                            }
                            if ('media1' === $filter) {
                                for ($i = 0; $i < 12; ++$i) {
                                    imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
                                    imagefilter($image, IMG_FILTER_SMOOTH, 6);
                                }
                                $function($image, $newDirname, 'png' === $extension ? 2 : 50);
                            } else {
                                if ('imagejpeg' === $function) {
                                    imageinterlace($image, true);
                                    $function($image, $newDirname, $quality);
                                } elseif ('imagewebp' === $function && 'png' === $extension && $this->pngHasAlpha($copyDirname)) {
                                    // WebP lossy with alpha preserved. Lossless inflated transparent PNG output ~5x.
                                    $function($image, $newDirname, $mediaQuality > 0 ? $mediaQuality : 78);
                                } else {
                                    $function($image, $newDirname, $quality);
                                }
                            }
                            // Si la version générée est plus lourde que la source, préférer la source
                            if ($this->filesystem->exists($copyDirname) && $this->filesystem->exists($newDirname)
                                && @filesize($newDirname) !== false && @filesize($copyDirname) !== false
                                && filesize($newDirname) > filesize($copyDirname)) {
                                $this->filesystem->copy($copyDirname, $newDirname, true);
                            }
                            // Only switch $path to the webp/avif variant if it was actually written.
                            // Prevents returning URLs pointing to non-existent files (404 in <picture>).
                            if ($this->filesystem->exists($newDirname)) {
                                $path = $newPath;
                            }
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

        // Final guard: if the resolved URL points to a missing file, regenerate it via
        // GD from the original uploaded source rather than returning a broken URL.
        $resolvedPath = str_replace(['//thumbnails', '/public'], ['/thumbnails', ''], $path);
        $localFile = $this->projectDirname.'/public'.str_replace($this->schemeAndHttpHost, '', $resolvedPath);
        $localFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $localFile);
        if (!$this->filesystem->exists($localFile)) {
            $uploadDir = ($media && $media->getWebsite()) ? $media->getWebsite()->getUploadDirname() : $this->uploadDirname;
            $originalName = $media ? $media->getOriginalName() : null;
            $sourceFile = $originalName && !str_contains($originalName, '/medias/') && !str_contains($originalName, '/build/')
                ? $this->projectDirname.'/public/uploads/'.$uploadDir.'/'.$originalName
                : $this->projectDirname.'/public'.($originalName ?: $dirname);
            $sourceFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sourceFile);

            if ($this->filesystem->exists($sourceFile)
                && $this->generateFallbackImage($sourceFile, $localFile, $runtimeConfig, (string) $filter)) {
                return $resolvedPath;
            }

            if ($this->filesystem->exists($sourceFile)) {
                $sourceUrl = $this->schemeAndHttpHost.str_replace([$this->projectDirname.'/public', '\\'], ['', '/'], $sourceFile);
                return str_replace(['//', '/public'], ['/', ''], $sourceUrl);
            }
        }

        return $resolvedPath;
    }

    /**
     * Fallback generator: encode a WebP at $targetFile from $sourceFile, applying
     * the resize implied by $runtimeConfig['thumbnail']['size'] and the lazy-blur
     * when the filter is 'media1'. Used when Liip / the primary pipeline did not
     * produce the expected file (avoids 404s on srcset variants).
     */
    private function generateFallbackImage(string $sourceFile, string $targetFile, array $runtimeConfig, string $filter): bool
    {
        if (!$this->filesystem->exists($sourceFile)) {
            return false;
        }

        $targetDir = dirname($targetFile);
        if (!$this->filesystem->exists($targetDir)) {
            try {
                $this->filesystem->mkdir($targetDir);
            } catch (\Throwable $e) {
                return false;
            }
        }

        $sourceExtension = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION));
        try {
            $img = match ($sourceExtension) {
                'png' => @imagecreatefrompng($sourceFile),
                'jpg', 'jpeg' => @imagecreatefromjpeg($sourceFile),
                'webp' => @imagecreatefromwebp($sourceFile),
                default => false,
            };
        } catch (\Throwable $e) {
            return false;
        }

        if (!$img instanceof \GdImage) {
            return false;
        }

        $imgWidth = imagesx($img);
        $imgHeight = imagesy($img);
        $expectedWidth = ('media1' !== $filter && isset($runtimeConfig['thumbnail']['size'][0]))
            ? (int) $runtimeConfig['thumbnail']['size'][0] : $imgWidth;
        $expectedHeight = ('media1' !== $filter && isset($runtimeConfig['thumbnail']['size'][1]))
            ? (int) $runtimeConfig['thumbnail']['size'][1] : $imgHeight;

        $image = imagecreatetruecolor($expectedWidth, $expectedHeight);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $trans = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, $expectedWidth - 1, $expectedHeight - 1, $trans);

        if ($expectedWidth === $imgWidth && $expectedHeight === $imgHeight) {
            imagecopy($image, $img, 0, 0, 0, 0, $imgWidth, $imgHeight);
        } else {
            imagecopyresampled($image, $img, 0, 0, 0, 0, $expectedWidth, $expectedHeight, $imgWidth, $imgHeight);
        }

        if ('media1' === $filter) {
            for ($i = 0; $i < 12; ++$i) {
                imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
                imagefilter($image, IMG_FILTER_SMOOTH, 6);
            }
            $written = @imagewebp($image, $targetFile, 'png' === $sourceExtension ? 2 : 50);
        } else {
            $written = @imagewebp($image, $targetFile, 78);
        }

        imagedestroy($img);
        imagedestroy($image);

        return $written && $this->filesystem->exists($targetFile);
    }

    /**
     * To get screen media.
     */
    private function getScreenMedia(string $screen, Media\Media $media): ?Media\Media
    {
        if ($media->isHaveMediaScreens()) {
            foreach ($media->getMediaScreens() as $mediaScreen) {
                if ($mediaScreen->getOriginalName() && $screen === $mediaScreen->getScreen()) {
                    return $mediaScreen;
                }
            }
        }

        if ($screen !== $media->getScreen()) {
            $mediaScreen = new Media\Media();
            $mediaScreen->setScreen($screen);
            $mediaScreen->setOriginalName($media->getOriginalName());
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
        $thumbnails['infos']['filename'] = $media->getOriginalName();
        $thumbnails['infos']['asDecor'] = $options['decor'] ?? 'svg' === $thumbnails['infos']['extension'];
        $thumbnails['infos']['shape'] = $mediaModel?->shape;

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

        $isAllowedExt = in_array($infos['extension'], self::ALLOWED_EXTENSIONS) || 'webp' === $infos['extension'];
        $attributes = $isAllowedExt ? '' : (($thumb instanceof Media\Thumb && $thumb->getWidth()) ? 'width="'.$thumb->getWidth().'"' : '');
        $attributes .= $isAllowedExt ? '' : (($thumb instanceof Media\Thumb && $thumb->getHeight()) ? ' height="'.$thumb->getHeight().'"' : '');
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
     * Generate a resized + minified SVG copy in /public/thumbnails/svg-resized.
     * Returns the public path of the generated file or null on failure.
     */
    private function resizeAndMinifySvg(string $svgPath, int $width, int $height): ?string
    {
        if (!$this->filesystem->exists($svgPath) || is_dir($svgPath)) {
            return null;
        }

        $publicDir = str_replace('\\', '/', $this->projectDirname) . '/public';
        $svgPathNorm = str_replace('\\', '/', $svgPath);
        if (!str_starts_with($svgPathNorm, $publicDir . '/')) {
            return null;
        }
        $relative = substr($svgPathNorm, strlen($publicDir) + 1);
        $pathInfo = pathinfo($relative);
        $outputRelative = self::SVG_RESIZED_DIR . '/' . trim($pathInfo['dirname'] ?? '', '/.') . '/' . $pathInfo['filename'] . '-' . $width . 'x' . $height . '.svg';
        $outputRelative = ltrim(preg_replace('#/+#', '/', $outputRelative), '/');
        $outputPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicDir . '/' . $outputRelative);

        if ($this->filesystem->exists($outputPath)) {
            return '/' . $outputRelative;
        }

        $content = @file_get_contents($svgPath);
        if (!$content) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (!$dom->loadXML($content)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return null;
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $svg = $dom->documentElement;
        if (!$svg instanceof \DOMElement || 'svg' !== strtolower($svg->localName)) {
            return null;
        }

        if (!$svg->hasAttribute('viewBox')) {
            $originalWidth = (float) $svg->getAttribute('width');
            $originalHeight = (float) $svg->getAttribute('height');
            if ($originalWidth > 0 && $originalHeight > 0) {
                $svg->setAttribute('viewBox', '0 0 ' . $originalWidth . ' ' . $originalHeight);
            }
        }
        $svg->setAttribute('width', (string) $width);
        $svg->setAttribute('height', (string) $height);

        $this->stripSvgEditorMetadata($dom);

        $output = $dom->saveXML($svg);
        if (!is_string($output) || '' === $output) {
            return null;
        }
        $output = preg_replace('/>\s+</', '><', $output);
        $output = preg_replace('/\s{2,}/', ' ', $output);

        $outputDir = dirname($outputPath);
        if (!$this->filesystem->exists($outputDir)) {
            try {
                $this->filesystem->mkdir($outputDir);
            } catch (\Exception $e) {
                return null;
            }
        }

        if (false === @file_put_contents($outputPath, $output)) {
            return null;
        }

        return '/' . $outputRelative;
    }

    /**
     * Strip Inkscape/Sodipodi metadata, comments and editor-only attributes.
     */
    private function stripSvgEditorMetadata(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
        $xpath->registerNamespace('inkscape', 'http://www.inkscape.org/namespaces/inkscape');
        $xpath->registerNamespace('sodipodi', 'http://sodipodi.sourceforge.net/DTD/sodipodi-0.0.dtd');

        $nodesToRemove = [];
        foreach ($xpath->query('//comment()') as $node) {
            $nodesToRemove[] = $node;
        }
        foreach ($xpath->query('//*[local-name()="metadata"]') as $node) {
            $nodesToRemove[] = $node;
        }
        foreach ($xpath->query('//inkscape:*') as $node) {
            $nodesToRemove[] = $node;
        }
        foreach ($xpath->query('//sodipodi:*') as $node) {
            $nodesToRemove[] = $node;
        }
        foreach ($nodesToRemove as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        foreach ($xpath->query('//*') as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }
            $attrsToDrop = [];
            foreach ($element->attributes as $attr) {
                if (in_array($attr->prefix, ['inkscape', 'sodipodi'], true)) {
                    $attrsToDrop[] = $attr->nodeName;
                }
            }
            foreach ($attrsToDrop as $name) {
                $element->removeAttribute($name);
            }
        }
    }

    /**
     * Build the thumbnail entries (files, thumbs, runtimeConfig) for an SVG media.
     */
    private function buildSvgThumbnail(array $thumbnails, string $screen, int $size, array $options, Media\Media $screenMedia, string $dirname): array
    {
        try {
            $svgDims = $this->getSvgDimensionsForScreen($screen, $size, $options, $screenMedia, $dirname);
            $svgWidth = !empty($svgDims['width']) ? (int) $svgDims['width'] : null;
            $svgHeight = !empty($svgDims['height']) ? (int) $svgDims['height'] : null;
            if (in_array($size, self::RETINA_SIZES)) {
                $thumbnails['files'][$size] = $this->publicPath($dirname);
            } else {
                $resizedSvg = ($svgWidth && $svgHeight) ? $this->resizeAndMinifySvg($dirname, $svgWidth, $svgHeight) : null;
                $thumbnails['files'][$size] = $resizedSvg ?: $this->publicPath($dirname);
            }
            $svgThumbConfig = new Media\ThumbConfiguration();
            $svgThumbConfig->setWidth($svgWidth);
            $svgThumbConfig->setHeight($svgHeight);
            $svgThumbConfig->setScreen($screen);
            $svgThumb = new Media\Thumb();
            $svgThumb->setWidth($svgWidth);
            $svgThumb->setHeight($svgHeight);
            $svgThumb->setConfiguration($svgThumbConfig);
            $svgThumb->setMedia($screenMedia);
            $thumbnails['thumbs'][$size] = (object) [
                'thumb' => $svgThumb,
                'media' => $screenMedia,
                'cropInfos' => (object) [
                    'width' => $svgWidth,
                    'height' => $svgHeight,
                    'dirname' => $dirname,
                    'extension' => 'svg',
                ],
            ];
            $thumbnails['runtimeConfig'][$size] = [];
        } catch (\Exception $e) {
            $thumbnails['files'][$size] = $this->publicPath($dirname);
        }

        return $thumbnails;
    }

    /**
     * Resolve the SVG dimensions to use for a given screen, based on options.
     */
    private function getSvgDimensionsForScreen(string $screen, int $size, array $options, ?Media\Media $media = null, ?string $dirname = null): array
    {
        $width = null;
        $height = null;

        if (!empty($options['screensSizes'][$screen])) {
            $screenOpts = $options['screensSizes'][$screen];
            $width = !empty($screenOpts['width']) ? (int) $screenOpts['width'] : null;
            $height = !empty($screenOpts['height']) ? (int) $screenOpts['height'] : null;
        }

        if (!$width && !$height) {
            $width = !empty($options['maxWidth']) ? (int) $options['maxWidth']
                : (!empty($options['width']) ? (int) $options['width'] : null);
            $height = !empty($options['maxHeight']) ? (int) $options['maxHeight']
                : (!empty($options['height']) ? (int) $options['height'] : null);
        }

        if ($media instanceof Media\Media && $dirname) {
            $svgInfo = $this->svgSizes($media, $dirname, null, null);
            $svgWidth = !empty($svgInfo['width']) ? (float) $svgInfo['width'] : null;
            $svgHeight = !empty($svgInfo['height']) ? (float) $svgInfo['height'] : null;
            if ($svgWidth && $svgHeight) {
                if ($width && !$height) {
                    $height = (int) ceil(($svgHeight * $width) / $svgWidth);
                } elseif ($height && !$width) {
                    $width = (int) ceil(($svgWidth * $height) / $svgHeight);
                } elseif (!$width && !$height) {
                    $width = (int) round($svgWidth);
                    $height = (int) round($svgHeight);
                }
            }
        }

        if (!$width && !$height) {
            $width = self::SCREENS_SIZES_ATTR[$screen] ?? 1920;
        }

        return ['width' => $width, 'height' => $height];
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
    /**
     * Detect alpha channel presence in a PNG (color types 4 and 6).
     * Reads only the IHDR chunk (29 first bytes) - no GD load required.
     */
    private function pngHasAlpha(string $filePath): bool
    {
        if (!$this->filesystem->exists($filePath) || is_dir($filePath)) {
            return false;
        }
        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        $header = fread($handle, 29);
        fclose($handle);
        if (!is_string($header) || strlen($header) < 26) {
            return false;
        }
        $colorType = ord($header[25]);

        return 4 === $colorType || 6 === $colorType;
    }

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
     * To get YAML sizes.
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
     * To preload media.
     */
    private function preload(array $thumbnails = [], array $options = []): void
    {
        if (!empty($options['screensSizes']) && !empty($options['priority']) && 'high' === $options['priority'] && !empty($thumbnails['files'])) {

            // Map file extensions to valid MIME types
            $mimeByExt = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'webp' => 'image/webp',
                'avif' => 'image/avif',
                'gif'  => 'image/gif',
                'svg'  => 'image/svg+xml',
                'ico'  => 'image/x-icon',
            ];

            $mediaQueries = [
                480  => '(max-width: 767px)',
                768  => '(min-width: 768px) and (max-width: 991px)',
                1200 => '(min-width: 992px) and (max-width: 1279px)',
                1366 => '(min-width: 1280px) and (max-width: 1599px)',
                1920 => '(min-width: 1600px)',
            ];

            $linkProvider = $this->coreLocator->request()->attributes->get('_links', new GenericLinkProvider());
            $providerByHref = [];
            foreach ($linkProvider->getLinks() as $link) {
                $providerByHref[$link->getHref()] = $link;
            }

            $inPreload = [];
            foreach ($thumbnails['files'] as $size => $file) {
                if (!isset($mediaQueries[$size])) {
                    continue;
                }
                $ext = strtolower(pathinfo(parse_url($file, PHP_URL_PATH) ?? $file, PATHINFO_EXTENSION));
                $attrMedia = $ext !== 'svg' && isset($mimeByExt[$ext]) ? $mediaQueries[$size] : 'all';
                if (!array_key_exists($file, $inPreload) && !array_key_exists($file, $providerByHref)) {
                    $link = (new Link('preload', $file))
                        ->withAttribute('as', 'image')
                        ->withAttribute('media', $attrMedia)
                        ->withAttribute('fetchpriority', 'high');
                    if ($ext !== '' && isset($mimeByExt[$ext])) {
                        $link = $link->withAttribute('type', $mimeByExt[$ext]);
                    }
                    $linkProvider = $linkProvider->withLink($link);
                    $inPreload[$file] = $link;
                }
            }

            $this->coreLocator->request()->attributes->set('_links', $linkProvider);
        }
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
