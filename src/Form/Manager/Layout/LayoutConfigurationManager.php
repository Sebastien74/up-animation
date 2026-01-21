<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout\LayoutConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * LayoutConfigurationManager.
 *
 * Manage admin Layout configuration form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => LayoutConfigurationManager::class, 'key' => 'layout_configuration_form_manager'],
])]
class LayoutConfigurationManager
{
    /**
     * LayoutConfigurationManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @prePersist
     */
    public function prePersist(LayoutConfiguration $layoutConfiguration, Website $website): void
    {
        $this->setEntity($layoutConfiguration);
        $this->entityManager->persist($layoutConfiguration);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(LayoutConfiguration $layoutConfiguration, Website $website): void
    {
        $this->setEntity($layoutConfiguration);
        $this->entityManager->persist($layoutConfiguration);
    }

    /**
     * Set LayoutConfiguration classname.
     */
    private function setEntity(LayoutConfiguration $layoutConfiguration): void
    {
        $layoutConfiguration->setEntity(str_replace('/', '\\', $layoutConfiguration->getEntity()));
    }
}
