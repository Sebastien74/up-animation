<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\FeedPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FeedPostRepository.
 *
 * @extends ServiceEntityRepository<FeedPost>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeedPostRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FeedPost::class);
    }

    /**
     * Active posts of a provider, newest first.
     *
     * @return FeedPost[]
     */
    public function findActiveByProvider(string $provider, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.provider = :provider')
            ->andWhere('p.removedAt IS NULL')
            ->setParameter('provider', $provider)
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->enableResultCache(3600, 'feed_post_active_'.$provider.'_'.$limit)
            ->getResult();
    }

    public function findOneByExternal(string $provider, string $externalId): ?FeedPost
    {
        return $this->findOneBy(['provider' => $provider, 'externalId' => $externalId]);
    }

    /**
     * @return FeedPost[]
     */
    public function findActiveExternalIds(string $provider): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.externalId')
            ->andWhere('p.provider = :provider')
            ->andWhere('p.removedAt IS NULL')
            ->setParameter('provider', $provider)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'externalId');
    }
}
