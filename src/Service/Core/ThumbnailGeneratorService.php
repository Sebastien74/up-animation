<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Core\Website;
use App\Entity\Media\Media;
use App\Entity\Media\ThumbConfiguration;
use App\Service\Content\ImageThumbnailInterface;
use App\Twig\Content\ThumbnailRuntime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Yaml\Yaml;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * ThumbnailCommand.
 *
 * To generate thumbnails
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ThumbnailGeneratorService
{
    private const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /**
     * ThumbnailGeneratorService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ThumbnailRuntime $thumbnailRuntime,
        private readonly ImageThumbnailInterface $imageThumbnail,
        private readonly FilterService $filterService,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Get media list for cache.
     */
    public function list(Website $website): array
    {
        $files = [];
        $websiteTemplate = $website->getConfiguration()->getTemplate();
        $uploadDirname = '/uploads/'.$website->getUploadDirname().'/';
        $uploadDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadDirname);

        /** DB Files */
        $medias = $this->entityManager->getRepository(Media::class)->findBy(['website' => $website]);
        foreach ($medias as $media) {
            $filename = $media->getFilename();
            $extension = $media->getExtension();
            if ($filename && !in_array($filename, $files) && $extension && in_array($extension, self::ALLOWED_EXTENSIONS)) {
                $dirname = $uploadDirname.$media->getFilename();
                $files[] = ['dirname' => str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname), 'filename' => $media->getFilename()];
            }
        }

        /** Build Files */
        $files = $this->getFiles('/public/build/front/'.$websiteTemplate.'/images/', '/build/front/'.$websiteTemplate.'/images/', $files);
        /** Public Medias Files */
        $files = $this->getFiles('/public/medias/', '/medias/', $files);
        $thumbs = $this->entityManager->getRepository(ThumbConfiguration::class)->findBy(['configuration' => $website->getConfiguration()]);

        return [
            'website' => $website,
            'count' => count($files),
            'files' => $files,
            'thumbs' => $thumbs,
        ];
    }

    /**
     * To resolve thumbnail.
     */
    public function resolve(Website $website, ThumbConfiguration $thumbConfiguration, string $dirname): void
    {
        $dirname = urldecode($dirname);
        $dirname = str_replace('/', '\\', $dirname);
        $matches = explode('\\', $dirname);
        $filename = end($matches);
        $media = $this->entityManager->getRepository(Media::class)->findOneBy(['website' => $website, 'filename' => $filename]);
        if ($media instanceof Media) {
            $thumbConfiguration = $this->thumbnailRuntime->thumbConfiguration($media, $thumbConfiguration);
            try {
                $this->thumbnailRuntime->thumb($media, $thumbConfiguration, ['execute' => true, 'path' => true, 'generator' => true]);
            } catch (LoaderError|RuntimeError|SyntaxError|NonUniqueResultException $e) {
            }
        }
    }

    /**
     * To generate by filter.
     */
    public function filters(string $dirname): void
    {
        $dirname = str_replace('\\', '/', urldecode($dirname));
        $yamlDirname = $this->projectDir.'/config/packages/liip_imagine.yaml';
        $yamlDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $yamlDirname);
        $yamlFilters = Yaml::parseFile($yamlDirname)['liip_imagine']['filter_sets'];
        $filters = ['media1', 'media10', 'media72', 'media100'];
        $excludesFilters = ['cache', 'admin_theme'];
        $fileDirname = $this->projectDir.'/public/'.$dirname;
        $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);

        $sizeLimit = $this->imageThumbnail->getMaxFileSize();
        $file = new File($fileDirname);
        $infos = new SplFileInfo($file->getRealPath(), $file->getPathname(), $file->getFilename());
        $fileSize = $infos->getSize();
        $fileDimensions = getimagesize($fileDirname);
        $width = !empty($fileDimensions[0]) ? $fileDimensions[0] : 0;
        $height = !empty($fileDimensions[1]) ? $fileDimensions[1] : 0;

        for ($i = 0; $i <= 100; ++$i) {
            $excludesFilters[] = 'media'.$i;
        }

        foreach ($yamlFilters as $filter => $configuration) {
            if (!in_array($filter, $excludesFilters)) {
                $filterSizes = !empty($configuration['filters']['thumbnail']['size']) ? $configuration['filters']['thumbnail']['size'] : null;
                $filterWidth = !empty($filterSizes[0]) ? $filterSizes[0] : null;
                $filterHeight = !empty($filterSizes[1]) ? $filterSizes[1] : null;
                if ((!$filterWidth && !$filterHeight) || ($filterWidth <= $width && $filterHeight <= $height)) {
                    $filters[] = $filter;
                }
            }
        }

        foreach ($filters as $filter) {
            if ($fileSize <= $sizeLimit) {
                //                set_time_limit(10);
                $this->filterService->getUrlOfFilteredImageWithRuntimeFilters($dirname, $filter);
            }
        }
    }

    /**
     * To get files in dirname.
     */
    private function getFiles(string $path, string $uri, array $files = []): array
    {
        $finder = Finder::create();
        $filesystem = new Filesystem();
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $assetDirname = $this->projectDir.$path;
        $assetDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $assetDirname);
        if ($filesystem->exists($assetDirname)) {
            $finder->files()->in($assetDirname)->name(['*.png', '*.jpg', '*.jpeg']);
            foreach ($finder as $file) {
                $dirname = $uri.str_replace([$this->projectDir, $path], '', $file->getPathname());
                $files[] = ['dirname' => str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname), 'filename' => $file->getFilename()];
            }
        }

        return $files;
    }
}
