<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Core\ConfigurationMediaRelation;
use App\Entity\Core\Website;
use App\Entity\Media\Media;
use App\Entity\Media\MediaIntl;
use App\Service\DataFixtures\UploadedFileFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * FaviconMediaSynchronizer.
 *
 * Aligns a website's favicon medias on the current RealFaviconGenerator set:
 * removes obsolete categories, provisions missing ones (multilingual) and
 * refreshes the kept files. Idempotent, safe to re-run.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class FaviconMediaSynchronizer
{
    private const array NEW_FAVICONS = [
        'favicon-svg' => 'favicon.svg',
        'favicon-96x96' => 'favicon-96x96.png',
        'web-app-manifest-192x192' => 'web-app-manifest-192x192.png',
        'web-app-manifest-512x512' => 'web-app-manifest-512x512.png',
    ];

    private const array REFRESH_FAVICONS = [
        'favicon' => 'favicon.ico',
        'favicon-apple-touch-icon' => 'apple-touch-icon.png',
    ];

    private const array OBSOLETE = [
        'favicon-16x16',
        'favicon-32x32',
        'android-chrome-144x144',
        'android-chrome-192x192',
        'android-chrome-512x512',
        'mstile-150x150',
        'mask-icon',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UploadedFileFixtures $uploadedFileFixtures,
        private WebsiteCacheInvalidator $websiteCacheInvalidator,
        private string $projectDir,
    ) {
    }

    /**
     * @return array{deleted: int, added: int, refreshed: int, deduped: int}
     */
    public function sync(Website $website, bool $dryRun = false): array
    {
        $configuration = $website->getConfiguration();
        $defaultLocale = $configuration->getLocale();
        $locales = $configuration->getAllLocales() ?: [$defaultLocale];
        $sourceDir = $this->projectDir.'/assets/medias/images/default/';
        $uploadDir = $this->projectDir.'/public/uploads/'.$website->getUploadDirname();
        $filesystem = new Filesystem();

        $existing = [];
        foreach ($configuration->getMediaRelations() as $relation) {
            $existing[$relation->getCategorySlug()] = true;
        }

        $deleted = $this->deleteObsolete($website, $dryRun, $filesystem, $uploadDir);
        $added = $this->addMissing($website, $existing, $defaultLocale, $locales, $sourceDir, $dryRun);
        $refreshed = $this->refreshKept($website, $sourceDir, $uploadDir, $dryRun, $filesystem);
        $deduped = $this->dedupe($website, $dryRun);

        if (!$dryRun && ($deleted || $added || $refreshed || $deduped)) {
            $this->invalidateLogosCache($website, $filesystem);
            $this->websiteCacheInvalidator->invalidate($website);
        }

        return ['deleted' => $deleted, 'added' => $added, 'refreshed' => $refreshed, 'deduped' => $deduped];
    }

    /**
     * Keep a single relation per (category, locale) across the current favicon set.
     */
    private function dedupe(Website $website, bool $dryRun): int
    {
        $configuration = $website->getConfiguration();
        $kept = array_merge(array_keys(self::NEW_FAVICONS), array_keys(self::REFRESH_FAVICONS));
        $seen = [];
        $removed = 0;

        foreach ($configuration->getMediaRelations()->toArray() as $relation) {
            if (!in_array($relation->getCategorySlug(), $kept, true)) {
                continue;
            }
            $signature = $relation->getCategorySlug().'|'.$relation->getLocale();
            if (isset($seen[$signature])) {
                ++$removed;
                if (!$dryRun) {
                    $configuration->removeMediaRelation($relation);
                    $this->entityManager->remove($relation);
                }
                continue;
            }
            $seen[$signature] = true;
        }

        if (!$dryRun && $removed) {
            $this->entityManager->flush();
        }

        return $removed;
    }

    private function deleteObsolete(Website $website, bool $dryRun, Filesystem $filesystem, string $uploadDir): int
    {
        $configuration = $website->getConfiguration();
        $count = 0;
        $medias = [];
        $files = [];

        foreach ($configuration->getMediaRelations()->toArray() as $relation) {
            if (!in_array($relation->getCategorySlug(), self::OBSOLETE, true)) {
                continue;
            }
            $media = $relation->getMedia();
            if ($media instanceof Media) {
                $medias[$media->getId()] = $media;
            }
            ++$count;
            if (!$dryRun) {
                $configuration->removeMediaRelation($relation);
                $this->entityManager->remove($relation);
            }
        }

        foreach ($medias as $media) {
            if ($media->getOriginalName()) {
                $files[] = $uploadDir.'/'.$media->getOriginalName();
            }
            if (!$dryRun) {
                $this->entityManager->remove($media);
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
            foreach ($files as $file) {
                $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
                if ($filesystem->exists($path)) {
                    $filesystem->remove($path);
                }
            }
        }

        return $count;
    }

    /**
     * @param array<string, bool> $existing
     * @param string[]            $locales
     */
    private function addMissing(Website $website, array $existing, string $defaultLocale, array $locales, string $sourceDir, bool $dryRun): int
    {
        $configuration = $website->getConfiguration();
        $added = 0;

        foreach (self::NEW_FAVICONS as $category => $filename) {
            if (!empty($existing[$category])) {
                continue;
            }
            $source = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sourceDir.$filename);
            if (!is_file($source)) {
                continue;
            }
            ++$added;
            if ($dryRun) {
                continue;
            }

            $media = $this->uploadedFileFixtures->uploadedFile($website, $source, $defaultLocale, $configuration, $category, $category);
            if (!$media instanceof Media) {
                --$added;
                continue;
            }

            foreach (array_diff($locales, [$defaultLocale]) as $locale) {
                $intl = new MediaIntl();
                $intl->setLocale($locale);
                $intl->setTitle($media->getName());
                $intl->setWebsite($website);
                $media->addIntl($intl);

                $relation = new ConfigurationMediaRelation();
                $relation->setLocale($locale);
                $relation->setMedia($media);
                $relation->setCategorySlug($category);
                $configuration->addMediaRelation($relation);
            }
            $this->entityManager->flush();
        }

        return $added;
    }

    private function refreshKept(Website $website, string $sourceDir, string $uploadDir, bool $dryRun, Filesystem $filesystem): int
    {
        $repository = $this->entityManager->getRepository(Media::class);
        $refreshed = 0;

        foreach (self::REFRESH_FAVICONS as $category => $filename) {
            $media = $repository->findOneBy(['website' => $website, 'category' => $category]);
            if (!$media instanceof Media || !$media->getOriginalName()) {
                continue;
            }
            $source = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sourceDir.$filename);
            $destination = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadDir.'/'.$media->getOriginalName());
            if (!is_file($source)) {
                continue;
            }
            ++$refreshed;
            if (!$dryRun) {
                $filesystem->copy($source, $destination, true);
            }
        }

        return $refreshed;
    }

    private function invalidateLogosCache(Website $website, Filesystem $filesystem): void
    {
        $pattern = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->projectDir.'/var/cache/*/website-logos-'.$website->getId().'.cache.json');
        foreach (glob($pattern) ?: [] as $cacheFile) {
            $filesystem->remove($cacheFile);
        }
    }
}
