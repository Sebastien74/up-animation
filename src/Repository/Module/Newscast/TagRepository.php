<?php

declare(strict_types=1);

namespace App\Repository\Module\Newscast;

use App\Entity\Module\Newscast\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TagRepository.
 *
 * @extends ServiceEntityRepository<Tag>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TagRepository extends ServiceEntityRepository
{
    /**
     * TagRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Tag::class);
    }
}
