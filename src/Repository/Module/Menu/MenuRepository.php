<?php

declare(strict_types=1);

namespace App\Repository\Module\Menu;

use App\Entity\Core\Website;
use App\Entity\Module\Menu\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MenuRepository.
 *
 * @extends ServiceEntityRepository<Menu>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MenuRepository extends ServiceEntityRepository
{
    /**
     * MenuRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Menu::class);
    }

    /**
     * Find Menu as array.
     */
    public function findArray(int $id): array
    {
        $result = $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        return !empty($result[0]) ? $result[0] : [];
    }

    /**
     * Find main.
     */
    public function findMain(Website $website): ?Menu
    {
        $menus = $this->createQueryBuilder('m')
            ->leftJoin('m.website', 'w')
            ->andWhere('m.website = :website')
            ->andWhere('m.main = :main')
            ->setParameter('website', $website->getId())
            ->setParameter('main', true)
            ->addSelect('w')
            ->getQuery()
            ->getResult();

        return !empty($menus[0]) ? $menus[0] : null;
    }
}
