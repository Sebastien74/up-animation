<?php

declare(strict_types=1);

namespace App\Repository\Module\Tab;

use App\Entity\Module\Tab\ContentIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ContentIntlRepository.
 *
 * @extends ServiceEntityRepository<ContentIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContentIntlRepository extends ServiceEntityRepository
{
    /**
     * ContentIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ContentIntl::class);
    }
}
