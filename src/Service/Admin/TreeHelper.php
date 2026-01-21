<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Layout\Page;
use App\Model\Core\WebsiteModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * TreeHelper.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => TreeHelper::class, 'key' => 'tree_helper'],
])]
class TreeHelper
{
    private QueryBuilder $queryBuilder;
    private ?object $baseEntity = null;

    /**
     * TreeHelper constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->queryBuilder = $this->entityManager->createQueryBuilder();
    }

    /**
     * Get entities for tree.
     */
    public function execute(string $classname, array $interface, WebsiteModel $website): array
    {
        $this->baseEntity = new $classname();
        if (method_exists($this->baseEntity, 'getUrls')) {
            return $this->getByUrls($classname, $website);
        } elseif (!empty($interface['masterField'])) {
            return $this->getByMasterField($classname, $interface);
        } else {
            return $this->getEntities($classname);
        }
    }

    /**
     * Get by URL.
     */
    private function getByUrls(string $classname, WebsiteModel $website): array
    {
        $queryBuilder = $this->queryBuilder->select('e')
            ->from($classname, 'e')
            ->leftJoin('e.urls', 'u')
            ->andWhere('u.archived = :archived')
            ->andWhere('u.website = :website')
            ->setParameter('archived', false)
            ->setParameter('website', $website->entity)
            ->addSelect('u');
        if ($this->baseEntity instanceof Page) {
            $queryBuilder->andWhere('e.deletable = :deletable')
                ->setParameter('deletable', true);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get by masterFiled.
     */
    private function getByMasterField(string $classname, array $interface): array
    {
        return $this->entityManager->getRepository($classname)
            ->findBy([
                $interface['masterField'] => $interface['masterFieldId'],
            ], ['position' => 'ASC']);
    }

    /**
     * Find All.
     */
    private function getEntities(string $classname): array
    {
        return $this->entityManager->getRepository($classname)->findBy([], ['position' => 'ASC']);
    }
}
