<?php

declare(strict_types=1);

namespace App\Form\Manager\Api;

use App\Entity\Api\Google;
use App\Entity\Api\GoogleIntl;
use App\Entity\Core\Website;
use App\Model\Seo\SeoConfigurationModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * GoogleManager.
 *
 * Manage admin GoogleManager form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => GoogleManager::class, 'key' => 'api_google_manager'],
])]
class GoogleManager
{
    /**
     * GoogleManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Synchronize locale entities.
     */
    public function synchronizeLocales(Website $website, SeoConfigurationModel $seoConfiguration): void
    {
        $configuration = $website->getConfiguration();
        $defaultLocale = $configuration->getLocale();
        $google = $seoConfiguration->entity->getWebsite()->getApi()->getGoogle();
        $this->entityManager->refresh($google);
        $defaultIntl = $this->getDefaultIntl($google, $defaultLocale);

        if ($defaultIntl) {
            foreach ($configuration->getAllLocales() as $locale) {
                if ($locale !== $defaultLocale) {
                    $existing = $this->localeExist($google, $locale);
                    if (!$existing) {
                        $this->add($google, $locale, $defaultIntl);
                    }
                }
            }
        }
    }

    /**
     * Get default locale GoogleIntl.
     */
    private function getDefaultIntl(Google $google, string $defaultLocale)
    {
        foreach ($google->getIntls() as $intl) {
            if ($intl->getLocale() === $defaultLocale) {
                return $intl;
            }
        }
        $this->add($google, $defaultLocale);
    }

    /**
     * Check if GoogleIntl locale exist.
     */
    private function localeExist(Google $google, string $locale): bool
    {
        foreach ($google->getIntls() as $googleIntl) {
            if ($googleIntl->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add GoogleIntl.
     */
    private function add(Google $google, string $locale, ?GoogleIntl $defaultGoogleIntl = null): void
    {
        $googleIntl = new GoogleIntl();
        $googleIntl->setLocale($locale);
        $googleIntl->setGoogle($google);

        if ($defaultGoogleIntl) {
            $googleIntl->setClientId($defaultGoogleIntl->getClientId());
            $googleIntl->setAnalyticsUa($defaultGoogleIntl->getAnalyticsUa());
            $googleIntl->setAnalyticsAccountId($defaultGoogleIntl->getAnalyticsAccountId());
            $googleIntl->setAnalyticsStatsDuration($defaultGoogleIntl->getAnalyticsStatsDuration());
            $googleIntl->setTagManagerKey($defaultGoogleIntl->getTagManagerKey());
            $googleIntl->setTagManagerLayer($defaultGoogleIntl->getTagManagerLayer());
            $googleIntl->setSearchConsoleKey($defaultGoogleIntl->getSearchConsoleKey());
            $googleIntl->setMapKey($defaultGoogleIntl->getMapKey());
            $googleIntl->setPlaceId($defaultGoogleIntl->getPlaceId());
        }

        $google->addIntl($googleIntl);
        $this->entityManager->persist($google);
        $this->entityManager->flush();
    }
}
