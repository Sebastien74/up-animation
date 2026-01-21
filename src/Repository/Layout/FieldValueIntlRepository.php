<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\FieldValueIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FieldValueIntlRepository.
 *
 * @extends ServiceEntityRepository<FieldValueIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FieldValueIntlRepository extends ServiceEntityRepository
{
    /**
     * FieldValueIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FieldValueIntl::class);
    }
}
