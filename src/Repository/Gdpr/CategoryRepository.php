<?php

declare(strict_types=1);

namespace App\Repository\Gdpr;

use App\Entity\Gdpr\Category;
use App\Model\Core\ConfigurationModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CategoryRepository.
 *
 * @extends ServiceEntityRepository<Category>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Category::class);
    }

    /**
     * Get by WebsiteModel ConfigurationModel & locale.
     *
     * @return array<Category>
     */
    public function findActiveByConfigurationAndLocale(ConfigurationModel $configuration, string $locale): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.gdprgroups', 'g')
            ->leftJoin('g.intls', 'i')
            ->andWhere('g.active = :active')
            ->andWhere('i.locale = :locale')
            ->andWhere('c.configuration = :configuration')
            ->setParameter('active', true)
            ->setParameter('locale', $locale)
            ->setParameter('configuration', $configuration->id)
            ->orderBy('c.id', 'ASC')
            ->addSelect('g')
            ->addSelect('i')
            ->getQuery()
            ->getResult();
    }
}
