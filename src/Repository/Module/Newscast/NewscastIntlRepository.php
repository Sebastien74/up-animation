<?php

declare(strict_types=1);

namespace App\Repository\Module\Newscast;

use App\Entity\Module\Newscast\NewscastIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * NewscastIntlRepository.
 *
 * @extends ServiceEntityRepository<NewscastIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewscastIntlRepository extends ServiceEntityRepository
{
    /**
     * NewscastIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, NewscastIntl::class);
    }
}
