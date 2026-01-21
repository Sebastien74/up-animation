<?php

declare(strict_types=1);

namespace App\Repository\Media;

use App\Entity\Media\Folder;
use App\Model\Core\WebsiteModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FolderRepository.
 *
 * @extends ServiceEntityRepository<Folder>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FolderRepository extends ServiceEntityRepository
{
    /**
     * FolderRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Folder::class);
    }

    /**
     * Find one by WebsiteModel.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByWebsite(WebsiteModel $website, int $id): ?Folder
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.medias', 'm')
            ->leftJoin('f.folders', 'fs')
            ->andWhere('f.id = :id')
            ->andWhere('f.website = :website')
            ->setParameter('id', $id)
            ->setParameter('website', $website->entity)
            ->addSelect('m')
            ->addSelect('fs')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by WebsiteModel.
     *
     * @return array<Folder>
     */
    public function findByWebsite(WebsiteModel $website): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.medias', 'm')
            ->leftJoin('f.folders', 'fs')
            ->andWhere('f.website = :website')
            ->setParameter('website', $website->entity)
            ->addSelect('m')
            ->addSelect('fs')
            ->getQuery()
            ->getResult();
    }
}
