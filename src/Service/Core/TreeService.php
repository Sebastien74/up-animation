<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Seo\Url;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * TreeService.
 *
 * To generate tree array of entity[]
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => TreeService::class, 'key' => 'tree_service'],
])]
class TreeService
{
    /**
     * TreeService constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Execute tree generator.
     */
    public function execute(mixed $entities): array
    {
        $tree = [];
        $positions = [];

        foreach ($entities as $entity) {

            $isConfigObject = is_object($entity) && property_exists($entity, 'entity');
            $configObject = $isConfigObject ? $entity : null;
            $entity = $isConfigObject ? $entity->entity : $entity;
            $push = true;

            if (is_object($entity) && method_exists($entity, 'getUrls')) {
                foreach ($entity->getUrls() as $url) {
                    /** @var Url $url */
                    if ($url->isArchived()) {
                        $push = false;
                    }
                }
            }

            if ($push) {
                $parent = $this->getParent($entity);
                $position = $this->getPosition($entity);
                $setPosition = !empty($positions[$parent]) && in_array($position, $positions[$parent]);
                $position = $setPosition ? count($positions[$parent]) + 1 : $position;
                $positions[$parent][] = $position;
                $tree[$parent][$position] = $isConfigObject ? $configObject : $entity;
                ksort($tree[$parent]);
                if (is_object($entity) && $setPosition) {
                    $entity->setPosition($position);
                    $this->entityManager->persist($entity);
                    $this->entityManager->flush();
                    $this->entityManager->refresh($entity);
                }
            }
        }

        /** To set parent keyName on each link */
        $treeInit = [];
        foreach ($tree as $key => $values) {
            $valuesInit = [];
            foreach ($values as $keyName => $value) {
                if (is_array($value)) {
                    $parentId = !empty($value['parent']['id']) ? $value['parent']['id'] : null;
                    foreach ($tree as $keyNameTree => $links) {
                        foreach ($links as $link) {
                            if ($link['id'] === $parentId) {
                                $value['parent']['keyName'] = $keyNameTree;
                                $valuesInit[$keyName] = $value;
                            }
                        }
                    }
                }
            }
            $treeInit[$key] = !empty($valuesInit) ? $valuesInit : $values;
        }

        return $treeInit;
    }

    /**
     * To get parent.
     */
    private function getParent($entity): mixed
    {
        return is_array($entity) && !empty($entity['parent']) ? $entity['parent']['id'] : (is_object($entity) && $entity->getParent() ? $entity->getParent()->getId() : 'main');
    }

    /**
     * To get position.
     */
    private function getPosition($entity): int
    {
        return is_array($entity) ? $entity['position'] : $entity->getPosition();
    }
}
