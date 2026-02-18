<?php

declare(strict_types=1);

namespace App\Form\Manager\Api;

use App\Entity\Api\Custom;
use App\Entity\Api\CustomIntl;
use App\Entity\Core\Website;
use App\Model\Seo\SeoConfigurationModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * CustomManager.
 *
 * Manage admin InstagramManager form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CustomManager::class, 'key' => 'api_custom_manager'],
])]
readonly class CustomManager
{
    /**
     * CustomManager constructor.
     */
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Synchronize locale entities.
     *
     * @throws ORMException
     */
    public function synchronizeLocales(Website $website, SeoConfigurationModel $seoConfiguration): void
    {
        $configuration = $website->getConfiguration();
        $defaultLocale = $configuration->getLocale();
        $custom = $seoConfiguration->entity->getWebsite()->getApi()->getCustom();
        $this->entityManager->refresh($custom);
        $defaultIntl = $this->getDefaultIntl($custom, $defaultLocale);
        if ($defaultIntl) {
            foreach ($configuration->getAllLocales() as $locale) {
                if ($locale !== $defaultLocale) {
                    $existing = $this->localeExist($custom, $locale);
                    if (!$existing) {
                        $this->add($custom, $locale, $defaultIntl);
                    }
                }
            }
        }
    }

    /**
     * Get default locale.
     */
    private function getDefaultIntl(Custom $custom, string $defaultLocale): ?CustomIntl
    {
        foreach ($custom->getIntls() as $intl) {
            if ($intl->getLocale() === $defaultLocale) {
                return $intl;
            }
        }

        return $this->add($custom, $defaultLocale);
    }

    /**
     * Check if a locale exists.
     */
    private function localeExist(Custom $custom, string $locale): bool
    {
        foreach ($custom->getIntls() as $customIntl) {
            if ($customIntl->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add.
     */
    private function add(Custom $custom, string $locale, ?CustomIntl $defaultCustomIntl = null): CustomIntl
    {
        $customIntl = new CustomIntl();
        $customIntl->setLocale($locale);
        $customIntl->setCustom($custom);
        $custom->addIntl($customIntl);
        $this->entityManager->persist($custom);
        $this->entityManager->flush();

        return $customIntl;
    }
}
