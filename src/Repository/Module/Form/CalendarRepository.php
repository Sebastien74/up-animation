<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Core\Website;
use App\Entity\Module\Form\Calendar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CalendarRepository.
 *
 * @extends ServiceEntityRepository<Calendar>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarRepository extends ServiceEntityRepository
{
    /**
     * CalendarRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Calendar::class);
    }

    /**
     * Find first by WebsiteModel.
     *
     * @throws NonUniqueResultException
     */
    public function findFirstByWebsite(Website $website): Calendar
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.form', 'f')
            ->andWhere('f.website = :website')
            ->setParameter('website', $website)
            ->orderBy('c.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
