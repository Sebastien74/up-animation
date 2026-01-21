<?php

declare(strict_types=1);

namespace App\Form\Manager\Information;

use App\Entity\Core\Website;
use App\Entity\Information\Information;
use App\Entity\Information\InformationIntl;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * InformationManager.
 *
 * Manage admin SocialNetwork form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => InformationManager::class, 'key' => 'info_form_manager'],
])]
class InformationManager
{
    /**
     * InformationManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Information $information, Website $website): void
    {
        $this->synchronizeLocales($information, $website);
    }

    /**
     * @prePersist
     */
    public function prePersist(Information $information, Website $website): void
    {
        $this->synchronizeLocales($information, $website);
    }

    /**
     * Synchronize locale entities.
     */
    private function synchronizeLocales(Information $information, Website $website): void
    {
        $configuration = $website->getConfiguration();
        $defaultLocale = $configuration->getLocale();
        $defaultIntl = $this->getDefaultIntl($information, $defaultLocale);

        if ($defaultIntl) {
            foreach ($information->getIntls() as $intl) {
                if ($intl->getLocale() !== $defaultLocale) {
                    $this->synchronizeLocale($defaultIntl, $intl);
                }
            }
        }
    }

    /**
     * Synchronize locale entity.
     */
    private function synchronizeLocale(InformationIntl $defaultIntl, InformationIntl $intl): void
    {
        if (!$intl->getTitle() && $defaultIntl->getTitle()) {
            $intl->setTitle($defaultIntl->getTitle());
            $this->entityManager->persist($intl);
        }
    }

    /**
     * Get default locale intl.
     */
    private function getDefaultIntl(Information $information, string $defaultLocale): ?InformationIntl
    {
        foreach ($information->getIntls() as $intl) {
            if ($intl->getLocale() === $defaultLocale) {
                return $intl;
            }
        }

        return null;
    }
}
