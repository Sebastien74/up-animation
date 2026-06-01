<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Core\Website;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * WebsiteCacheInvalidator.
 *
 * Instantly invalidates a website's rendered cache by bumping its cacheClearDate
 * (versioned key of the `{% cache %}` fragments and page result-cache) and flushing
 * the cache.app pool (which also backs the Doctrine result cache).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class WebsiteCacheInvalidator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $appCache,
    ) {
    }

    public function invalidate(Website $website): void
    {
        $website->setCacheClearDate(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        $this->em->persist($website);
        $this->em->flush();

        $this->appCache->clear();
    }
}
