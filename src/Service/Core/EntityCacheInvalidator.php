<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use App\Message\InvalidateCacheItems;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * EntityCacheInvalidator.
 *
 * Invalidates a single entity's rendered cache from its edit view. Two complementary moves:
 * - if the entity owns a layout (Page, or Product/Newscast with customLayout), bumps its
 *   blocks' updatedAt so the {% cache %} fragments of its own page regenerate, and lets
 *   CacheInvalidationSubscriber clear the matching page result-cache on flush;
 * - for any layout-bearing entity (shared layout included), deletes the Doctrine result-cache
 *   keys backing the module action that renders it (pages_action_*) plus the result-cache of
 *   the pages that pin it, found via PageRepository::findAllByActionForLocales.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class EntityCacheInvalidator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly RenderedCacheKeyResolver $cacheKeyResolver,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function supports(object $entity): bool
    {
        return method_exists($entity, 'getLayout') && method_exists($entity, 'getId') && $entity->getId();
    }

    public function invalidate(object $entity): void
    {
        $this->bumpOwnLayout($entity);
        $this->invalidateRelatedRenderedCache($entity);
    }

    private function bumpOwnLayout(object $entity): void
    {
        $layout = $this->resolveLayout($entity);
        if (!$layout instanceof Layout) {
            return;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $touched = false;
        foreach ($layout->getZones() as $zone) {
            foreach ($zone->getCols() as $col) {
                foreach ($col->getBlocks() as $block) {
                    $block->setUpdatedAt($now);
                    $this->em->persist($block);
                    $touched = true;
                }
            }
        }

        if ($touched) {
            $this->em->flush();
        }
    }

    private function invalidateRelatedRenderedCache(object $entity): void
    {
        if (!method_exists($entity, 'getId') || !$entity->getId()) {
            return;
        }

        $website = $this->coreLocator->website();
        if (null === $website) {
            return;
        }

        $class = $this->em->getClassMetadata(get_class($entity))->getName();
        $id = $entity->getId();
        $locales = $website->configuration->allLocales ?? [];

        $keys = $this->cacheKeyResolver->actionKeys($class, $id, $website);

        if ($locales && null !== $website->entity) {
            $pages = $this->em->getRepository(Page::class)->findAllByActionForLocales($website->entity, $locales, $class, [$id]);
            foreach ($pages as $page) {
                $keys = array_merge($keys, $this->cacheKeyResolver->pageKeys($page, $website));
            }
        }

        $keys = array_values(array_unique($keys));
        if (!$keys) {
            return;
        }

        try {
            $this->messageBus->dispatch(new InvalidateCacheItems($keys));
            return;
        } catch (\Throwable) {
        }

        $cache = $this->em->getConfiguration()->getResultCache();
        if ($cache) {
            try {
                $cache->deleteItems($keys);
            } catch (\Throwable) {
            }
        }
    }

    private function resolveLayout(object $entity): ?Layout
    {
        if (!method_exists($entity, 'getLayout')) {
            return null;
        }

        $layout = $entity->getLayout();

        return $layout instanceof Layout ? $layout : null;
    }
}
