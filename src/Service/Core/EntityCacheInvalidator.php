<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Layout\Layout;
use Doctrine\ORM\EntityManagerInterface;

/**
 * EntityCacheInvalidator.
 *
 * Invalidates a single entity's rendered cache by bumping the updatedAt of its layout
 * blocks. Combined with the block.updatedAt segment of the `{% cache %}` fragment key,
 * this regenerates only that entity's fragments; flushing in the admin context also lets
 * CacheInvalidationSubscriber clear the matching page/action result-cache, exactly as a
 * content edit would.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class EntityCacheInvalidator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function supports(object $entity): bool
    {
        $layout = $this->resolveLayout($entity);

        return $layout instanceof Layout && !$layout->getZones()->isEmpty();
    }

    public function invalidate(object $entity): void
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

    private function resolveLayout(object $entity): ?Layout
    {
        if (!method_exists($entity, 'getLayout')) {
            return null;
        }

        $layout = $entity->getLayout();

        return $layout instanceof Layout ? $layout : null;
    }
}
