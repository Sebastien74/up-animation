<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core\Color;
use App\Entity\Core\Configuration;
use App\Entity\Core\ConfigurationMediaRelation;
use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Entity\Information\SocialNetwork;
use App\Entity\Media\Media;
use App\Entity\Media\MediaRelation;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Content\ColorRuntime;
use App\Twig\Translation\i18nRuntime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * ConfigurationManager.
 *
 * Manage admin ConfigurationModel form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ConfigurationManager::class, 'key' => 'core_configuration_form_manager'],
])]
class ConfigurationManager
{
    private const array ADD_CUSTOM_DEFAULT_MEDIAS = [
        'logo-secondary',
        'webmanifest',
        'security-front-logo',
        'security-logo',
        'security-bg',
        'build-logo',
        'build-bg',
        'user-condition',
    ];

    private ?Request $request;

    /**
     * ConfigurationManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly i18nRuntime $i18nRuntime,
        private readonly ColorRuntime $colorRuntime,
        private readonly string $projectDir,
    ) {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * Synchronize locale relation entities.
     *
     * @throws NonUniqueResultException|MappingException|InvalidArgumentException|\ReflectionException
     */
    public function synchronizeLocales(Configuration $configuration): void
    {
        $this->synchronizeMedias($configuration);
        $this->synchronizeNetworks($configuration);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Configuration $configuration): void
    {
        $this->generateStylesheetFile($configuration);
        $this->clearManifest($configuration);
    }

    /**
     * To generate custom stylesheet file.
     */
    private function generateStylesheetFile(Configuration $configuration): void
    {
        $defaultColors = ['primary', 'secondary', 'success', 'info', 'warning', 'danger', 'danger-light', 'light', 'dark', 'white'];
        $prefixes = ['alert' => 'alert', 'background' => 'bg', 'button' => 'btn', 'color' => 'txt'];
        $pushColors = [];

        foreach ($configuration->getColors() as $color) {
            $category = $color->getCategory();
            $colorSlug = !empty($prefixes[$category]) ? str_replace([$prefixes[$category].'-', 'outline-'], '', $color->getSlug()) : null;
            if ($colorSlug && !in_array($colorSlug, $defaultColors) && 'link' !== $colorSlug && $color->isActive()) {
                $pushColors[] = $color;
            }
        }

        if ($pushColors) {
            $stylesheet = '';
            foreach ($pushColors as $pushColor) {
                $category = $pushColor->getCategory();
                if ('alert' === $category || 'background' === $category || 'button' === $category) {
                    $stylesheet .= '.'.$pushColor->getSlug().'{background-color: '.$pushColor->getColor().'}';
                } elseif ('color' === $category) {
                    $stylesheet .= '.'.$pushColor->getSlug().'{color: '.$pushColor->getColor().'}';
                }
            }

            if ($stylesheet) {
                $filesystem = new Filesystem();
                $dirname = $this->projectDir.'/public/uploads/stylesheets/';
                $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
                $filename = 'custom-app-'.$configuration->getWebsite()->getUploadDirname().'.css';
                if (!$filesystem->exists($dirname)) {
                    $filesystem->mkdir($dirname, 0777);
                }
                file_put_contents($dirname.$filename, $stylesheet);
            }
        }
    }

    /**
     * To clear manifest file.
     */
    private function clearManifest(Configuration $configuration): void
    {
        $website = $configuration->getWebsite();
        $filename = 'manifest.webmanifest.'.$website->getSlug().'.json';
        $publicDirname = $this->projectDir.'/public/';
        $dirname = $publicDirname.$filename;
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $filesystem = new Filesystem();
        if ($filesystem->exists($dirname)) {
            $filesystem->remove($dirname);
        }
    }

    /**
     * Synchronize Medias.
     *
     * @throws NonUniqueResultException|MappingException|InvalidArgumentException|\ReflectionException
     */
    private function synchronizeMedias(Configuration $configuration): void
    {
        $defaultLocalesMedias = $this->getDefaultLocaleMedias($configuration);
        $defaultLocale = $configuration->getLocale();
        $repository = $this->entityManager->getRepository(ConfigurationMediaRelation::class);
        $flush = false;
        $this->setManifest($configuration);

        foreach (self::ADD_CUSTOM_DEFAULT_MEDIAS as $category) {
            $existing = false;
            foreach ($defaultLocalesMedias as $defaultLocaleMedia) {
                if ($defaultLocaleMedia->getCategorySlug() === $category) {
                    $existing = true;
                }
            }
            if (!$existing) {
                foreach ($configuration->getAllLocales() as $locale) {
                    $this->addMedia($locale, $configuration, null, $category);
                    $this->entityManager->persist($configuration);
                    $this->entityManager->flush();
                    $this->entityManager->refresh($configuration);
                }
            }
        }

        foreach ($defaultLocalesMedias as $mediaRelation) {
            foreach ($configuration->getAllLocales() as $locale) {
                if ($locale !== $defaultLocale && !in_array($mediaRelation->getCategorySlug(), self::ADD_CUSTOM_DEFAULT_MEDIAS)) {
                    $existing = $repository->findDefaultLocaleCategory($configuration->getWebsite(), $mediaRelation->getCategorySlug(), $locale);
                    if (!$existing) {
                        $this->addMedia($locale, $configuration, $mediaRelation);
                        $flush = true;
                    }
                }
            }
        }

        if ($flush) {
            $this->entityManager->persist($configuration);
            $this->entityManager->flush();
        }
    }

    /**
     * To generate manifest file.
     *
     * @throws NonUniqueResultException|MappingException|InvalidArgumentException|\ReflectionException
     */
    private function setManifest(Configuration $configuration): void
    {
        $protocol = $this->request->isSecure() ? 'https://' : 'http://';
        $locale = $configuration->getLocale();
        $filesystem = new Filesystem();
        $website = $configuration->getWebsite();
        $websiteModel = \App\Model\Core\WebsiteModel::fromEntity($website, $this->coreLocator, $locale);
        $domains = $this->entityManager->getRepository(Domain::class)->findBy(['configuration' => $configuration, 'locale' => $locale, 'asDefault' => true]);
        $domain = !empty($domains[0]) ? $domains[0]->getName() : $this->request->getSchemeAndHttpHost();
        $information = $website instanceof Website ? $this->i18nRuntime->intl($website->getInformation(), $locale) : null;
        $name = $information ? $information->getTitle() : null;
        $logos = $website instanceof Website ? $websiteModel->configuration->logos : [];
        $theme = $website instanceof Website ? $this->colorRuntime->color('favicon', $websiteModel, 'webmanifest-theme') : null;
        $background = $website instanceof Website ? $this->colorRuntime->color('favicon', $websiteModel, 'webmanifest-background') : null;

        $icons = [];
        $uploadDirname = $website->getUploadDirname();
        $publicDir = $this->projectDir.'/public';
        $files = ['android-chrome-144x144' => '144x144', 'android-chrome-192x192' => '192x192', 'android-chrome-512x512' => '512x512'];
        foreach ($files as $fileName => $size) {
            if (!empty($logos[$fileName])) {
                $fileDirname = $publicDir.$logos[$fileName];
                if ($filesystem->exists($fileDirname)) {
                    $file = new File(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname));
                    $icons[] = [
                        'src' => '/uploads/'.$uploadDirname.'/'.$fileName.'.'.$file->getExtension(),
                        'sizes' => $size,
                        'type' => 'image/'.$file->getExtension(),
                    ];
                }
            }
        }

        $response = new JsonResponse([
            'name' => $name,
            'short_name' => $name,
            'description' => $name,
            'icons' => $icons,
            'start_url' => $protocol.$domain,
            'display' => 'standalone', /* or fullscreen */
            'theme_color' => $theme instanceof Color && $theme->isActive() ? $theme->getColor() : '#ffffff',
            'background_color' => $background instanceof Color && $background->isActive() ? $background->getColor() : '#ffffff',
        ]);
        $response->setEncodingOptions(16);
        $dirname = $this->projectDir.'/public/uploads/'.$website->getUploadDirname().'/manifest.webmanifest.json';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $filesystem->dumpFile($dirname, $response->getContent());

        $mediaRelations = $this->entityManager->getRepository(MediaRelation::class)->findBy(['categorySlug' => 'webmanifest']);
        foreach ($mediaRelations as $mediaRelation) {
            $media = $mediaRelation->getMedia();
            if ($media instanceof Media && $media->getWebsite()->getId() === $website->getId()) {
                $media->setFilename('manifest.webmanifest.json');
                $media->setName('manifest.webmanifest');
                $media->setExtension('json');
                $this->entityManager->persist($media);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Synchronize SocialNetwork.
     */
    private function synchronizeNetworks(Configuration $configuration): void
    {
        $flush = false;
        $information = $configuration->getWebsite()->getInformation();
        $existingLocales = [];
        foreach ($information->getSocialNetworks() as $socialNetwork) {
            $existingLocales[] = $socialNetwork->getLocale();
        }

        foreach ($configuration->getAllLocales() as $locale) {
            if (!in_array($locale, $existingLocales)) {
                $socialNetwork = new SocialNetwork();
                $socialNetwork->setLocale($locale);
                $information->addSocialNetwork($socialNetwork);
                $flush = true;
            }
        }

        if ($flush) {
            $this->entityManager->persist($configuration);
            $this->entityManager->flush();
        }
    }

    /**
     * Get default medias.
     */
    private function getDefaultLocaleMedias(Configuration $configuration): array
    {
        $medias = [];
        $defaultLocale = $configuration->getLocale();
        foreach ($configuration->getMediaRelations() as $mediaRelation) {
            if ($mediaRelation->getLocale() === $defaultLocale) {
                $medias[] = $mediaRelation;
            }
        }

        return $medias;
    }

    /**
     * Add Media.
     */
    private function addMedia(string $locale, Configuration $configuration, ?ConfigurationMediaRelation $defaultRelation = null, ?string $category = null): void
    {
        $media = $defaultRelation instanceof ConfigurationMediaRelation ? $defaultRelation->getMedia() : new Media();
        $category = $defaultRelation instanceof ConfigurationMediaRelation ? $defaultRelation->getCategorySlug() : $category;
        $media->setWebsite($configuration->getWebsite());
        $media->setCategory($category);
        $mediaRelation = new ConfigurationMediaRelation();
        $mediaRelation->setLocale($locale);
        $mediaRelation->setCategorySlug($category);
        $mediaRelation->setMedia($media);
        $configuration->addMediaRelation($mediaRelation);
    }
}
