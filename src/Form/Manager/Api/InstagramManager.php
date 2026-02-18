<?php

declare(strict_types=1);

namespace App\Form\Manager\Api;

use App\Entity\Core\Website;
use App\Model\Seo\SeoConfigurationModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
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
readonly class InstagramManager
{
    /**
     * InstagramManager constructor.
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
        $instagram = $website->getApi()->getInstagram();
        $this->entityManager->refresh($instagram);
    }
}
