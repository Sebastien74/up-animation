<?php

declare(strict_types=1);

namespace App\Repository\Module\Newsletter;

use App\Entity\Core\Website;
use App\Entity\Module\Newsletter\Campaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
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
            ->enableResultCache(3600, 'newsletter-campaign-'.md5($website->getId().'_'.$locale.'_'.$filter))
            ->getOneOrNullResult();
    }

    /**
     * Admin index QueryBuilder with intls preloaded for the current locale.
     *
     * Returned as a QueryBuilder so it can be paginated by the Knp paginator.
     */
    public function createAdminIndexQueryBuilder(Website $website, string $locale): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.intls', 'i', 'WITH', 'i.locale = :locale')
            ->andWhere('c.website = :website')
            ->setParameter('website', $website)
            ->setParameter('locale', $locale)
            ->addSelect('i')
            ->orderBy('c.position', 'ASC');
    }
}
