<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\ProductInformation;
use App\Model\Core\WebsiteModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ProductInformationRepository.
 *
 * @extends ServiceEntityRepository<ProductInformation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProductInformationRepository extends ServiceEntityRepository
{
    /**
     * ProductInformationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ProductInformation::class);
    }

    /**
     * Index by ids.
     *
     * @throws NonUniqueResultException|QueryException
     */
    public function findByProduct(Product $product): ?ProductInformation
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.product', 'p')
            ->leftJoin('i.address', 'a')
            ->leftJoin('i.socialNetworks', 's')
            ->andWhere('p.id = :id')
            ->setParameter('id', $product->getId())
            ->addSelect('a')
            ->addSelect('s')
            ->indexBy('i', 'i.id')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
