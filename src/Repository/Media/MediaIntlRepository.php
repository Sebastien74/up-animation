<?php

declare(strict_types=1);

namespace App\Repository\Media;

use App\Entity\Media\MediaIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MediaIntlRepository.
 *
 * @extends ServiceEntityRepository<MediaIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaIntlRepository extends ServiceEntityRepository
{
    /**
     * MediaIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, MediaIntl::class);
    }
}
