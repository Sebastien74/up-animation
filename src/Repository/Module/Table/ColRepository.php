<?php

declare(strict_types=1);

namespace App\Repository\Module\Table;

use App\Entity\Module\Table\Col;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ColRepository.
 *
 * @extends ServiceEntityRepository<Col>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ColRepository extends ServiceEntityRepository
{
    /**
     * ColRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Col::class);
    }
}
