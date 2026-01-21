<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core\Entity;
use App\Entity\Core\Website;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * EntityConfigurationManager.
 *
 * Manage admin Entity configuration form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => EntityConfigurationManager::class, 'key' => 'core_entity_configuration_form_manager'],
])]
class EntityConfigurationManager
{
    /**
     * EntityConfigurationManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @prePersist
     */
    public function prePersist(Entity $entity, Website $website): void
    {
        $this->setClassName($entity);
        $this->entityManager->persist($entity);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Entity $entity, Website $website): void
    {
        $this->setClassName($entity);

        $this->entityManager->persist($entity);
    }

    /**
     * Set Entity classname.
     */
    private function setClassName(Entity $entity): void
    {
        $entity->setClassName(str_replace('/', '\\', $entity->getClassName()));
    }
}
