<?php

declare(strict_types=1);

namespace App\Repository\Module\Search;

use App\Entity\Module\Search\Embedding;
use App\Entity\Module\Search\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * EmbeddingRepository.
 *
 * @extends ServiceEntityRepository<Search>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EmbeddingRepository extends ServiceEntityRepository
{
    /**
     * EmbeddingRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Embedding::class);
    }
}
