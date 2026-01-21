<?php

declare(strict_types=1);

namespace App\Repository\Module\Agenda;

use App\Entity\Core\Website;
use App\Entity\Module\Agenda\Agenda;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AgendaRepository.
 *
 * @extends ServiceEntityRepository<Agenda>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AgendaRepository extends ServiceEntityRepository
{
    /**
     * AgendaRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Agenda::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Agenda
    {
        $statement = $this->createQueryBuilder('a')
            ->leftJoin('a.website', 'w')
            ->leftJoin('a.periods', 'p')
            ->addSelect('w', 'p');

        if (is_numeric($filter)) {
            $statement->andWhere('a.id = :id')
                ->setParameter('id', $filter);
        } else {
            $statement->andWhere('a.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $statement->getQuery()
            ->getOneOrNullResult();
    }
}
