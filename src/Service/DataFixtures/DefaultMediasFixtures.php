<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Media as MediaEntities;
use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * DefaultMediasFixtures.
 *
 * DefaultMedia Fixtures management.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => DefaultMediasFixtures::class, 'key' => 'default_medias_fixtures'],
])]
readonly class DefaultMediasFixtures
{
    /**
     * DefaultMediasFixtures constructor.
     */
    public function __construct(
        private CoreLocatorInterface   $coreLocator,
        private EntityManagerInterface $entityManager,
        private UploadedFileFixtures   $uploader,
        private string                 $projectDir,
    ) {
    }

    /**
     * Add defaults Medias.
     */
    public function add(Website $website, array $yamlConfiguration, ?User $user = null): MediaEntities\Folder
    {
        $configuration = $website->getConfiguration();
        $locale = $configuration->getLocale();
        $projectPath = !empty($yamlConfiguration['media_path_duplication']) ? $yamlConfiguration['media_path_duplication'] : 'default';

        $webmasterFolder = $this->uploader->generateFolder($website, 'Webmaster', 'webmaster', null, $user);
        $mainFolder = $this->uploader->generateFolder($website, 'Images principales', 'default-media', $webmasterFolder, $user);
        $this->uploader->generateFolder($website, 'Pictos', 'pictogram', null, $user, false);
        $this->uploader->generateFolder($website, 'Pages', 'page', null, $user, false);
        $this->uploader->generateFolder($website, 'Actualités', 'newscast', null, $user, false);
        $this->uploader->generateFolder($website, 'Carousels', 'slider', null, $user, false);
        $this->uploader->generateFolder($website, 'Navigations', 'menu', null, $user, false);

        foreach ($this->getMedias() as $keyName => $infos) {
            $filename = !empty($yamlConfiguration['files'][$keyName]) ? $yamlConfiguration['files'][$keyName] : $infos->filename;
            $directory = $infos->doc ? 'docs' : 'images';
            $path = $this->projectDir.'/assets/medias/'.$directory.'/'.$projectPath.'/'.$filename;
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            $media = null;
            if ($filename) {
                $media = $this->uploader->uploadedFile($website, $path, $locale, $configuration, $infos->category, $keyName, $user);
            } else {
                $this->setMedia($website, $keyName, $locale, $user);
            }
            if ($media instanceof MediaEntities\Media) {
                $media->setFolder($mainFolder);
                $this->entityManager->persist($media);
                $this->entityManager->flush();
            }
        }

        return $webmasterFolder;
    }

    /**
     * To set Media with empty filename.
     */
    private function setMedia(Website $website, string $name, string $locale, ?User $user = null): void
    {
        $media = new MediaEntities\Media();
        $media->setWebsite($website);
        $media->setName($name);
        $media->setCategory($name);
        $media->setCreatedBy($user);

        $intl = new MediaEntities\MediaIntl();
        $intl->setLocale($locale);
        $intl->setTitle($name);
        $intl->setCreatedBy($user);
        $intl->setWebsite($website);
        $media->addIntl($intl);

        $configuration = $website->getConfiguration();
        $mediaRelationData = $this->coreLocator->metadata($configuration, 'mediaRelations');
        $relation = new ($mediaRelationData->targetEntity)();
        $relation->setLocale($locale);
        $relation->setMedia($media);
        $relation->setCategorySlug($name);
        $configuration->addMediaRelation($relation);
        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        if ($user) {
            $relation->setCreatedBy($user);
        }
    }

    /**
     * Get Media[] configurations.
     */
    private function getMedias(): array
    {
        return [
            'logo' => (object) ['category' => 'logo', 'filename' => 'logo.png', 'doc' => false],
            'favicon' => (object) ['category' => 'favicon', 'filename' => 'favicon.ico', 'doc' => false],
            'favicon-svg' => (object) ['category' => 'favicon-svg', 'filename' => 'favicon.svg', 'doc' => false],
            'favicon-96x96' => (object) ['category' => 'favicon-96x96', 'filename' => 'favicon-96x96.png', 'doc' => false],
            'favicon-apple-touch-icon' => (object) ['category' => 'favicon-apple-touch-icon', 'filename' => 'apple-touch-icon.png', 'doc' => false],
            'web-app-manifest-192x192' => (object) ['category' => 'web-app-manifest-192x192', 'filename' => 'web-app-manifest-192x192.png', 'doc' => false],
            'web-app-manifest-512x512' => (object) ['category' => 'web-app-manifest-512x512', 'filename' => 'web-app-manifest-512x512.png', 'doc' => false],
            'share' => (object) ['category' => 'share', 'filename' => 'share.jpg', 'doc' => false],
            'preloader' => (object) ['category' => 'preloader', 'filename' => 'preloader.png', 'doc' => false],
            'footer' => (object) ['category' => 'footer', 'filename' => 'footer-logo.png', 'doc' => false],
            'email' => (object) ['category' => 'email', 'filename' => 'email-logo.png', 'doc' => false],
            'admin' => (object) ['category' => 'admin', 'filename' => 'admin-logo.png', 'doc' => false],
            'title-header' => (object) ['category' => 'title-header', 'filename' => 'title-header.jpg', 'doc' => false],
            'placeholder' => (object) ['category' => 'placeholder', 'filename' => 'placeholder.jpg', 'doc' => false],
            'facebook' => (object) ['category' => 'social-network', 'filename' => 'facebook.svg', 'doc' => false],
            'google' => (object) ['category' => 'social-network', 'filename' => 'google.svg', 'doc' => false],
            'twitter' => (object) ['category' => 'social-network', 'filename' => 'twitter.svg', 'doc' => false],
            'youtube' => (object) ['category' => 'social-network', 'filename' => 'youtube.svg', 'doc' => false],
            'tiktok' => (object) ['category' => 'social-network', 'filename' => 'tiktok.svg', 'doc' => false],
            'instagram' => (object) ['category' => 'social-network', 'filename' => 'instagram.svg', 'doc' => false],
            'linkedin' => (object) ['category' => 'social-network', 'filename' => 'linkedin.svg', 'doc' => false],
            'pinterest' => (object) ['category' => 'social-network', 'filename' => 'pinterest.svg', 'doc' => false],
            'tripadvisor' => (object) ['category' => 'social-network', 'filename' => 'tripadvisor.svg', 'doc' => false],
            'security-front-logo' => (object) ['category' => 'security-front-logo', 'filename' => null, 'doc' => false],
            'security-logo' => (object) ['category' => 'security-logo', 'filename' => null, 'doc' => false],
            'security-bg' => (object) ['category' => 'security-bg', 'filename' => null, 'doc' => false],
            'build-logo' => (object) ['category' => 'build-logo', 'filename' => null, 'doc' => false],
            'build-bg' => (object) ['category' => 'build-bg', 'filename' => null, 'doc' => false],
        ];
    }
}
