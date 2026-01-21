<?php

declare(strict_types=1);

namespace App\Repository\Media;

use App\Entity\Media\MediaRelationIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MediaRelationIntlRepository.
 *
 * @extends ServiceEntityRepository<MediaRelationIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaRelationIntlRepository extends ServiceEntityRepository
{
    /**
     * MediaRelationIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, MediaRelationIntl::class);
    }
}
