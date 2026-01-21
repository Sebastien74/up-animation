<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\FieldValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FieldValueRepository.
 *
 * @extends ServiceEntityRepository<FieldValue>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FieldValueRepository extends ServiceEntityRepository
{
    /**
     * FieldValueRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FieldValue::class);
    }
}
