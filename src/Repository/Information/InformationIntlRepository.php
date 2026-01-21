<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\InformationIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * InformationIntlRepository.
 *
 * @extends ServiceEntityRepository<InformationIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InformationIntlRepository extends ServiceEntityRepository
{
    /**
     * InformationIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, InformationIntl::class);
    }
}
