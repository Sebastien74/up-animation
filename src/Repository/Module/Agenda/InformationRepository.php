<?php

declare(strict_types=1);

namespace App\Repository\Module\Agenda;

use App\Entity\Module\Agenda\Information;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * InformationRepository.
 *
 * @extends ServiceEntityRepository<Information>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InformationRepository extends ServiceEntityRepository
{
    /**
     * InformationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Information::class);
    }
}
