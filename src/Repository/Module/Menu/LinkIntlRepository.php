<?php

declare(strict_types=1);

namespace App\Repository\Module\Menu;

use App\Entity\Module\Menu\LinkIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LinkIntlRepository.
 *
 * @extends ServiceEntityRepository<LinkIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LinkIntlRepository extends ServiceEntityRepository
{
    /**
     * LinkRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, LinkIntl::class);
    }
}
