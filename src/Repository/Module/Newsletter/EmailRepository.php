<?php

declare(strict_types=1);

namespace App\Repository\Module\Newsletter;

use App\Entity\Module\Newsletter\Email;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * EmailRepository.
 *
 * @extends ServiceEntityRepository<Email>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EmailRepository extends ServiceEntityRepository
{
    /**
     * EmailRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Email::class);
    }

    /**
     * To get all expired Email[].
     */
    public function findEmailsWithExpiredToken(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.tokenDate IS NOT NULL')
            ->andWhere('e.tokenDate < :dateLimit')
            ->setParameter('dateLimit', new \DateTimeImmutable('-24 hours'))
            ->getQuery()
            ->getResult();
    }
}
