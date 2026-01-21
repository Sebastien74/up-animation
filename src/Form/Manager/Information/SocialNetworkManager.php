<?php

declare(strict_types=1);

namespace App\Form\Manager\Information;

use App\Entity\Core\Website;
use App\Entity\Information\SocialNetwork;
use App\Entity\Seo\SeoConfiguration;
use App\Model\Seo\SeoConfigurationModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * SocialNetworkManager.
 *
 * Manage admin SocialNetwork form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SocialNetworkManager::class, 'key' => 'info_networks_form_manager'],
])]
class SocialNetworkManager
{
    /**
     * SocialNetworkManager constructor.
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
        $defaultIntl = $this->getDefaultIntl($seoConfiguration->entity, $defaultLocale);

        if ($defaultIntl) {
            foreach ($configuration->getAllLocales() as $locale) {
                if ($locale !== $defaultLocale) {
                    $existing = $this->localeExist($seoConfiguration->entity, $locale);
                    if (!$existing) {
                        $this->add($seoConfiguration->entity, $locale, $defaultIntl);
                    }
                }
            }
        }
    }

    /**
     * Get default locale SocialNetwork.
     */
    private function getDefaultIntl(SeoConfiguration $seoConfiguration, string $defaultLocale): ?SocialNetwork
    {
        $socialNetworks = $seoConfiguration->getWebsite()->getInformation()->getSocialNetworks();
        foreach ($socialNetworks as $socialNetwork) {
            if ($socialNetwork->getLocale() === $defaultLocale) {
                return $socialNetwork;
            }
        }

        return null;
    }

    /**
     * Check if SocialNetwork locale exist.
     */
    private function localeExist(SeoConfiguration $seoConfiguration, string $locale): bool
    {
        $socialNetworks = $seoConfiguration->getWebsite()->getInformation()->getSocialNetworks();
        foreach ($socialNetworks as $socialNetwork) {
            if ($socialNetwork->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add Locale SocialNetwork.
     */
    private function add(SeoConfiguration $seoConfiguration, string $locale, SocialNetwork $defaultIntl): void
    {
        $information = $seoConfiguration->getWebsite()->getInformation();

        $socialNetwork = new SocialNetwork();
        $socialNetwork->setLocale($locale);
        $socialNetwork->setTwitter($defaultIntl->getTwitter());
        $socialNetwork->setFacebook($defaultIntl->getFacebook());
        $socialNetwork->setGoogle($defaultIntl->getGoogle());
        $socialNetwork->setYoutube($defaultIntl->getYoutube());
        $socialNetwork->setInstagram($defaultIntl->getInstagram());
        $socialNetwork->setLinkedin($defaultIntl->getLinkedin());
        $socialNetwork->setPinterest($defaultIntl->getPinterest());
        $socialNetwork->setTripadvisor($defaultIntl->getTripadvisor());
        $socialNetwork->setTiktok($defaultIntl->getTiktok());

        $information->addSocialNetwork($socialNetwork);

        $this->entityManager->persist($information);
        $this->entityManager->flush();
        $this->entityManager->refresh($information);
    }
}
