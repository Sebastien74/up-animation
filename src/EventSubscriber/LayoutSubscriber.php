<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Layout\Block;
use App\Entity\Layout\BlockIntl;
use App\Entity\Layout\Col;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Zone;
use App\Entity\Layout\ZoneIntl;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

#[AsDoctrineListener(event: Events::onFlush)]
class LayoutSubscriber
{
    /**
     * onFlush.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions()
        );

        $layoutsToUpdate = [];

        foreach ($entities as $entity) {
            $layout = $this->findLayout($entity);
            if ($layout instanceof Layout) {
                $layoutsToUpdate[$layout->getId()] = $layout;
            }
        }

        foreach ($layoutsToUpdate as $layout) {
            $this->updateParent($layout, $em, $uow);
        }
    }

    /**
     * Find Layout from various layout components.
     */
    private function findLayout(object $entity): ?Layout
    {
        if ($entity instanceof Layout) {
            return $entity;
        }

        if ($entity instanceof Zone) {
            return $entity->getLayout();
        }

        if ($entity instanceof ZoneIntl) {
            return $entity->getZone() ? $entity->getZone()->getLayout() : null;
        }

        if ($entity instanceof Col) {
            return $entity->getZone() ? $entity->getZone()->getLayout() : null;
        }

        if ($entity instanceof Block) {
            return $entity->getCol() && $entity->getCol()->getZone() ? $entity->getCol()->getZone()->getLayout() : null;
        }

        if ($entity instanceof BlockIntl) {
            return $entity->getBlock() && $entity->getBlock()->getCol() && $entity->getBlock()->getCol()->getZone()
                ? $entity->getBlock()->getCol()->getZone()->getLayout() : null;
        }

        return null;
    }

    /**
     * Update parent entity of the layout.
     */
    private function updateParent(Layout $layout, EntityManagerInterface $em, UnitOfWork $uow): void
    {
        $parent = $layout->getParent($em);
        if (is_object($parent) && method_exists($parent, 'setUpdatedAt')) {
            $parent->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($parent)), $parent);
        }
    }
}
