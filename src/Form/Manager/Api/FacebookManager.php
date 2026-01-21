<?php

declare(strict_types=1);

namespace App\Form\Manager\Api;

use App\Entity\Api\Facebook;
use App\Entity\Api\FacebookIntl;
use App\Entity\Core\Website;
use App\Model\Seo\SeoConfigurationModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * FacebookManager.
 *
 * Manage admin FacebookManager form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => FacebookManager::class, 'key' => 'api_facebook_manager'],
])]
class FacebookManager
{
    /**
     * FacebookManager constructor.
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
        $facebook = $seoConfiguration->entity->getWebsite()->getApi()->getFacebook();
        $this->entityManager->refresh($facebook);
        $defaultIntl = $this->getDefaultIntl($facebook, $defaultLocale);

        if ($defaultIntl) {
            foreach ($configuration->getAllLocales() as $locale) {
                if ($locale !== $defaultLocale) {
                    $existing = $this->localeExist($facebook, $locale);
                    if (!$existing) {
                        $this->add($facebook, $locale, $defaultIntl);
                    }
                }
            }
        }
    }

    /**
     * Get default locale FacebookIntl.
     */
    private function getDefaultIntl(Facebook $facebook, string $defaultLocale): ?FacebookIntl
    {
        foreach ($facebook->getIntls() as $intl) {
            if ($intl->getLocale() === $defaultLocale) {
                return $intl;
            }
        }

        return $this->add($facebook, $defaultLocale);
    }

    /**
     * Check if FacebookIntls locale exist.
     */
    private function localeExist(Facebook $facebook, string $locale): bool
    {
        foreach ($facebook->getIntls() as $facebookIntl) {
            if ($facebookIntl->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add FacebookIntl.
     */
    private function add(Facebook $facebook, string $locale, ?FacebookIntl $defaultFacebookIntl = null): FacebookIntl
    {
        $facebookIntl = new FacebookIntl();
        $facebookIntl->setLocale($locale);
        $facebookIntl->setFacebook($facebook);

        $facebook->addIntl($facebookIntl);

        $this->entityManager->persist($facebook);
        $this->entityManager->flush();

        return $facebookIntl;
    }
}
