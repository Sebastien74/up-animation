<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\PageIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PageIntlRepository.
 *
 * @extends ServiceEntityRepository<PageIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PageIntlRepository extends ServiceEntityRepository
{
    /**
     * PageIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, PageIntl::class);
    }
}
