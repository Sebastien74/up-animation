<?php

declare(strict_types=1);

namespace App\Repository\Module\Menu;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Module\Menu\Link;
use App\Entity\Module\Menu\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LinkRepository.
 *
 * @extends ServiceEntityRepository<Link>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LinkRepository extends ServiceEntityRepository
{
    /**
     * LinkRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Link::class);
    }

    /**
     * Find by Menu and locale.
     *
     * @return array<Link>
     */
    public function findByMenuAndLocale(array $menu, string $locale): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.menu', 'm')
            ->andWhere('l.menu = :menu')
            ->andWhere('l.locale = :locale')
            ->setParameter('menu', $menu['id'])
            ->setParameter('locale', $locale)
            ->orderBy('l.position', 'ASC')
            ->addSelect('m')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by Page and locale.
     */
    public function findByPageAndLocale(
        Page $page,
        string $locale,
        ?Menu $menu = null,
        bool $onlyOne = false,
    ): array|Link|null {

        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.intl', 'i')
            ->andWhere('i.locale = :locale')
            ->andWhere('i.targetPage = :page')
            ->setParameter('locale', $locale)
            ->setParameter('page', $page)
            ->addSelect('i');

        if ($menu instanceof Menu) {
            $qb->andWhere('l.menu = :menu')
                ->setParameter('menu', $menu);
        }

        $result = $qb->getQuery()->getResult();

        if ($onlyOne) {
            return !empty($result[0]) ? $result[0] : null;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find by Page.
     *
     * @return array<Link>
     */
    public function findByPage(Page $page): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.intl', 'i')
            ->andWhere('i.targetPage = :page')
            ->setParameter('page', $page)
            ->addSelect('i')
            ->getQuery()
            ->getResult();
    }
}
