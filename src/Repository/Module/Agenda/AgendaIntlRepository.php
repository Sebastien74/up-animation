<?php

declare(strict_types=1);

namespace App\Repository\Module\Agenda;

use App\Entity\Module\Agenda\AgendaIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AgendaIntlRepository.
 *
 * @extends ServiceEntityRepository<AgendaIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AgendaIntlRepository extends ServiceEntityRepository
{
    /**
     * AgendaIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, AgendaIntl::class);
    }
}
