<?php

declare(strict_types=1);

namespace App\Repository\Module\Newsletter;

use App\Entity\Core\Website;
use App\Entity\Module\Newsletter\Campaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CampaignRepository.
 *
 * @extends ServiceEntityRepository<Campaign>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CampaignRepository extends ServiceEntityRepository
{
    /**
     * CampaignRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Campaign::class);
    }

    /**
     * Find by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Campaign
    {
        $statement = $this->createQueryBuilder('c')
            ->leftJoin('c.website', 'w')
            ->leftJoin('c.intls', 'i')
            ->andWhere('c.website = :website')
            ->andWhere('i.locale = :locale')
            ->setParameter('website', $website)
            ->setParameter('locale', $locale)
            ->addSelect('w')
            ->addSelect('i');

        if (is_numeric($filter)) {
            $statement->andWhere('c.id = :id')
                ->setParameter('id', $filter);
        } else {
            $statement->andWhere('c.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $statement->getQuery()
            ->getOneOrNullResult();
    }
}
