<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\EmailIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * EmailIntlRepository.
 *
 * @extends ServiceEntityRepository<EmailIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EmailIntlRepository extends ServiceEntityRepository
{
    /**
     * EmailIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, EmailIntl::class);
    }
}
