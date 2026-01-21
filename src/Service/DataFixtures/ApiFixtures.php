<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Api as ApiEntities;
use App\Entity\Core\Website;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ApiFixtures.
 *
 * ApiModel Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ApiFixtures::class, 'key' => 'api_fixtures'],
])]
class ApiFixtures
{
    private array $yamlConfiguration = [];

    /**
     * ApiFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add ApiModel.
     */
    public function add(Website $website, array $yamlConfiguration): void
    {
        $this->yamlConfiguration = $yamlConfiguration;
        $configuration = $website->getConfiguration();
        $locales = $configuration->getAllLocales();
        $defaultLocale = $configuration->getLocale();

        $api = new ApiEntities\Api();
        $api->setWebsite($website);
        $website->setApi($api);

        foreach ($locales as $locale) {
            $this->addGoogle($api, $locale, $defaultLocale);
        }

        $this->addFacebook($api, $locales);
        $this->addInstagram($api, $locales);
        $this->addCustom($api, $locales);

        $this->entityManager->persist($api);
    }

    /**
     * Add Google.
     */
    private function addGoogle(ApiEntities\Api $api, string $locale, string $defaultLocale): void
    {
        $google = $api->getGoogle();

        if (!$google instanceof ApiEntities\Google) {
            $google = new ApiEntities\Google();
            $google->setApi($api);
            $api->setGoogle($google);
        }

        $intl = new ApiEntities\GoogleIntl();
        $intl->setLocale($locale);
        $intl->setGoogle($google);

        $apiData = !empty($this->yamlConfiguration['apis'][$locale]['google'])
            ? $this->yamlConfiguration['apis'][$locale]['google'] : (!empty($this->yamlConfiguration['apis'][$defaultLocale]['google'])
                ? $this->yamlConfiguration['apis'][$defaultLocale]['google'] : []);

        if (!empty($apiData['ua'])) {
            $intl->setAnalyticsUa($apiData['ua']);
        }

        if (!empty($apiData['tag_manager'])) {
            $intl->setTagManagerKey($apiData['tag_manager']);
        }

        $this->entityManager->persist($intl);
        $this->entityManager->persist($google);
    }

    /**
     * Add Facebook.
     */
    private function addFacebook(ApiEntities\Api $api, array $locales): void
    {
        $facebook = new ApiEntities\Facebook();
        $facebook->setApi($api);
        foreach ($locales as $locale) {
            $facebookIntl = new ApiEntities\FacebookIntl();
            $facebookIntl->setLocale($locale);
            $facebook->addIntl($facebookIntl);
        }
        $api->setFacebook($facebook);
        $this->entityManager->persist($facebook);
    }

    /**
     * Add Instagram.
     */
    private function addInstagram(ApiEntities\Api $api, array $locales): void
    {
        $instagram = new ApiEntities\Instagram();
        $instagram->setApi($api);
        foreach ($locales as $locale) {
            $instagramIntl = new ApiEntities\InstagramIntl();
            $instagramIntl->setLocale($locale);
            $instagram->addIntl($instagramIntl);
        }
        $api->setInstagram($instagram);
        $this->entityManager->persist($instagram);
    }

    /**
     * Add Custom.
     */
    private function addCustom(ApiEntities\Api $api, array $locales): void
    {
        $custom = new ApiEntities\Custom();
        $custom->setApi($api);
        foreach ($locales as $locale) {
            $customIntl = new ApiEntities\CustomIntl();
            $customIntl->setLocale($locale);
            $custom->addIntl($customIntl);
        }
        $api->setCustom($custom);
        $this->entityManager->persist($custom);
    }
}
