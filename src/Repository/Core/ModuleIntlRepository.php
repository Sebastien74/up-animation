<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\ModuleIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ModuleIntlRepository.
 *
 * @extends ServiceEntityRepository<ModuleIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ModuleIntlRepository extends ServiceEntityRepository
{
    /**
     * ModuleIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ModuleIntl::class);
    }
}
