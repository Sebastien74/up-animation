<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\Picture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PictureRepository.
 *
 * @extends ServiceEntityRepository<Picture>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PictureRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Picture::class);
    }
}
