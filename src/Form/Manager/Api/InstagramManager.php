<?php

declare(strict_types=1);

namespace App\Form\Manager\Api;

use App\Entity\Api\Instagram;
use App\Entity\Api\InstagramIntl;
use App\Entity\Core\Website;
use App\Model\Seo\SeoConfigurationModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * InstagramManager.
 *
 * Manage admin InstagramManager form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => InstagramManager::class, 'key' => 'api_instagram_manager'],
])]
class InstagramManager
{
    /**
     * InstagramManager constructor.
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
        $instagram = $website->getApi()->getInstagram();
        $this->entityManager->refresh($instagram);
        $defaultIntl = $this->getDefaultIntl($instagram, $defaultLocale);

        if ($defaultIntl) {
            foreach ($configuration->getAllLocales() as $locale) {
                if ($locale !== $defaultLocale) {
                    $existing = $this->localeExist($instagram, $locale);
                    if (!$existing) {
                        $this->add($instagram, $locale, $defaultIntl);
                    }
                }
            }
        }
    }

    /**
     * Get default locale InstagramIntl.
     */
    private function getDefaultIntl(Instagram $instagram, string $defaultLocale): ?InstagramIntl
    {
        foreach ($instagram->getIntls() as $intl) {
            if ($intl->getLocale() === $defaultLocale) {
                return $intl;
            }
        }

        return $this->add($instagram, $defaultLocale);
    }

    /**
     * Check if InstagramIntls locale exist.
     */
    private function localeExist(Instagram $instagram, string $locale): bool
    {
        foreach ($instagram->getIntls() as $instagramIntl) {
            if ($instagramIntl->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add InstagramIntl.
     */
    private function add(Instagram $instagram, string $locale, ?InstagramIntl $defaultInstagramIntl = null): InstagramIntl
    {
        $instagramIntl = new InstagramIntl();
        $instagramIntl->setLocale($locale);
        $instagramIntl->setInstagram($instagram);

        $instagram->addIntl($instagramIntl);

        $this->entityManager->persist($instagram);
        $this->entityManager->flush();

        return $instagramIntl;
    }
}
