<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Entity\Seo\SeoConfiguration;
use App\Entity\Seo\SeoConfigurationIntl;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * SeoFixtures.
 *
 * Seo Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SeoFixtures::class, 'key' => 'seo_fixtures'],
])]
class SeoFixtures
{
    /**
     * SeoFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add Seo.
     */
    public function add(Website $website, ?User $user = null): void
    {
        $configuration = $website->getConfiguration();
        $seoConfiguration = $this->addConfiguration($website, $user);
        $allLocales = $configuration->getAllLocales();
        foreach ($allLocales as $locale) {
            $this->addIntl($website, $seoConfiguration, $locale, $user);
        }
        $this->entityManager->persist($website);
    }

    /**
     * Add Seo ConfigurationModel.
     */
    private function addConfiguration(Website $website, ?User $user = null): SeoConfiguration
    {
        $configuration = new SeoConfiguration();
        $configuration->setWebsite($website);
        $configuration->setCreatedBy($user);
        $website->setSeoConfiguration($configuration);

        return $configuration;
    }

    /**
     * Add Seo ConfigurationModel.
     */
    private function addIntl(Website $website, SeoConfiguration $configuration, string $locale, ?User $user = null): void
    {
        $intl = new SeoConfigurationIntl();
        $intl->setLocale($locale);
        $intl->setAuthorType('Organization');
        $intl->setPlaceholder('Organization');
        $intl->setWebsite($website);
        $intl->setCreatedBy($user);
        $configuration->addIntl($intl);
    }
}
