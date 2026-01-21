<?php

declare(strict_types=1);

namespace App\Repository\Module\Tab;

use App\Entity\Module\Tab\Content;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ContentRepository.
 *
 * @extends ServiceEntityRepository<Content>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContentRepository extends ServiceEntityRepository
{
    /**
     * ContentRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Content::class);
    }
}
