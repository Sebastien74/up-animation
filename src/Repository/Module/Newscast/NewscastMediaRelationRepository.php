<?php

declare(strict_types=1);

namespace App\Repository\Module\Newscast;

use App\Entity\Module\Newscast\NewscastMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * NewscastMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<NewscastMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewscastMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * NewscastMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, NewscastMediaRelation::class);
    }
}
