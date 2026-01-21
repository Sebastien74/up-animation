<?php

declare(strict_types=1);

namespace App\Repository\Module\Table;

use App\Entity\Module\Table\ColIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ColIntlRepository.
 *
 * @extends ServiceEntityRepository<ColIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ColIntlRepository extends ServiceEntityRepository
{
    /**
     * ColIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ColIntl::class);
    }
}
