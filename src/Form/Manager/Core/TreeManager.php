<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * TreeManager.
 *
 * Manage Entities in tree in admin
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => TreeManager::class, 'key' => 'core_tree_form_manager']
])]
class TreeManager
{
    /**
     * TreeManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Set positions of tree elements.
     */
    public function post(array $data, string $classname): void
    {
        $outputs = json_decode($data['nestable_output']);
        $this->setPositionsByLevel($classname, $outputs, 1, null);
    }

    /**
     * Set positions of tree elements by level.
     */
    private function setPositionsByLevel(string $classname, array $outputs, int $level, $parent = null): void
    {
        $repository = $this->entityManager->getRepository($classname);
        $position = 1;
        foreach ($outputs as $output) {
            $entity = $repository->find($output->id);
            if (!empty($entity)) {
                $entity->setPosition($position);
                if (method_exists($entity, 'setLevel')) {
                    $entity->setLevel($level);
                }
                if (method_exists($entity, 'setParent')) {
                    $entity->setParent($parent);
                }
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                if (property_exists($output, 'children') && !empty($output->children)) {
                    $this->setPositionsByLevel($classname, $output->children, $level + 1, $entity);
                }
                ++$position;
            }
        }
    }
}
