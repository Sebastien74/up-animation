<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Zone;
use App\Entity\Layout\Col;
use App\Entity\Layout\Block;
use App\Entity\Layout\PageIntl;
use App\Entity\Layout\ZoneIntl;
use App\Entity\Layout\BlockIntl;
use App\Entity\Module\Table\Table;
use App\Entity\Module\Table\TableIntl;
use App\Entity\Module\Table\Col as TableCol;
use App\Entity\Module\Table\ColIntl as TableColIntl;
use App\Entity\Module\Table\Cell;
use App\Entity\Module\Table\CellIntl;
use App\Entity\Media\MediaRelationIntl;
use App\Entity\Seo\Url;
use DateMalformedStringException;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\MappingException;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[AsDoctrineListener(event: Events::onFlush)]
class CacheInvalidationSubscriber
{
    private \Symfony\Component\PropertyAccess\PropertyAccessor $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * onFlush.
     *
     * @throws DateMalformedStringException|MappingException
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

        $websitesToInvalidate = [];
        $pagesToInvalidate = [];

        foreach ($entities as $entity) {
            if ($entity instanceof BaseInterface) {
                $page = $this->findPage($entity, $em);
                if ($page instanceof Page) {
                    $pagesToInvalidate[$page->getId()] = $page;
                } else {
                    $website = $this->findWebsite($entity, $em);
                    if ($website instanceof Website) {
                        $websitesToInvalidate[$website->getId()] = $website;
                    }
                }
            }
        }

        foreach ($pagesToInvalidate as $page) {
            $page->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(Page::class), $page);
        }

        foreach ($websitesToInvalidate as $website) {
            $website->setCacheClearDate(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(Website::class), $website);
        }
    }

    /**
     * findPage.
     */
    private function findPage(object $entity, EntityManagerInterface $em): ?Page
    {
        if ($entity instanceof Page) {
            return $entity;
        }

        $class = get_class($entity);
        if ($em->getMetadataFactory()->isTransient($class)) {
            return null;
        }

        $metadata = $em->getClassMetadata($class);

        // Try 'page' property
        if ($metadata->hasAssociation('page')) {
            try {
                $page = $this->propertyAccessor->getValue($entity, 'page');
                if ($page instanceof Page) {
                    return $page;
                }
            } catch (\Exception $e) {}
        }

        // Try 'layout' property
        if ($metadata->hasAssociation('layout')) {
            try {
                $layout = $this->propertyAccessor->getValue($entity, 'layout');
                if ($layout instanceof Layout) {
                    $page = $em->getRepository(Page::class)->findOneBy(['layout' => $layout]);
                    if ($page) {
                        return $page;
                    }
                }
            } catch (\Exception $e) {}
        }

        // Try 'zone' property
        if ($metadata->hasAssociation('zone')) {
            try {
                $zone = $this->propertyAccessor->getValue($entity, 'zone');
                if ($zone) {
                    return $this->findPage($zone, $em);
                }
            } catch (\Exception $e) {}
        }

        // Try 'col' property
        if ($metadata->hasAssociation('col')) {
            try {
                $col = $this->propertyAccessor->getValue($entity, 'col');
                if ($col) {
                    return $this->findPage($col, $em);
                }
            } catch (\Exception $e) {}
        }

        // Try 'block' property
        if ($metadata->hasAssociation('block')) {
            try {
                $block = $this->propertyAccessor->getValue($entity, 'block');
                if ($block) {
                    return $this->findPage($block, $em);
                }
            } catch (\Exception $e) {}
        }

        // Try to find via masterField
        if (method_exists($class, 'getMasterField')) {
            $masterField = $class::getMasterField();
            if ($masterField && $metadata->hasAssociation($masterField)) {
                try {
                    $parent = $this->propertyAccessor->getValue($entity, $masterField);
                    if ($parent) {
                        return $this->findPage($parent, $em);
                    }
                } catch (\Exception $e) {}
            }
        }

        return null;
    }

    /**
     * findWebsite.
     *
     * @throws MappingException
     */
    private function findWebsite(object $entity, EntityManagerInterface $em): ?Website
    {
        if ($entity instanceof Website) {
            return $entity;
        }

        $class = get_class($entity);
        if ($em->getMetadataFactory()->isTransient($class)) {
            return null;
        }

        $metadata = $em->getClassMetadata($class);
        
        // Try 'website' property if exists
        if ($metadata->hasAssociation('website')) {
            try {
                return $this->propertyAccessor->getValue($entity, 'website');
            } catch (\Exception $e) {}
        }

        // Try to find via masterField if defined in the entity class
        if (method_exists($class, 'getMasterField')) {
            $masterField = $class::getMasterField();
            if ($masterField && $metadata->hasAssociation($masterField)) {
                try {
                    $parent = $this->propertyAccessor->getValue($entity, $masterField);
                    if ($parent) {
                        return $this->findWebsite($parent, $em);
                    }
                } catch (\Exception $e) {}
            }
        }

        // Try 'col' property for Table/ColIntl
        if ($metadata->hasAssociation('col')) {
            try {
                $col = $this->propertyAccessor->getValue($entity, 'col');
                if ($col) {
                    return $this->findWebsite($col, $em);
                }
            } catch (\Exception $e) {}
        }

        // Try 'table' property for TableIntl
        if ($metadata->hasAssociation('table')) {
            try {
                $table = $this->propertyAccessor->getValue($entity, 'table');
                if ($table) {
                    return $this->findWebsite($table, $em);
                }
            } catch (\Exception $e) {}
        }

        // Try 'zone' property for ZoneIntl
        if ($metadata->hasAssociation('zone')) {
            try {
                $zone = $this->propertyAccessor->getValue($entity, 'zone');
                if ($zone) {
                    return $this->findWebsite($zone, $em);
                }
            } catch (\Exception $e) {}
        }

        // Try 'block' property for BlockIntl
        if ($metadata->hasAssociation('block')) {
            try {
                $block = $this->propertyAccessor->getValue($entity, 'block');
                if ($block) {
                    return $this->findWebsite($block, $em);
                }
            } catch (\Exception $e) {}
        }

        // Try 'page' property for PageIntl
        if ($metadata->hasAssociation('page')) {
            try {
                $page = $this->propertyAccessor->getValue($entity, 'page');
                if ($page) {
                    return $this->findWebsite($page, $em);
                }
            } catch (\Exception $e) {}
        }

        // Try 'cell' property for CellIntl
        if ($metadata->hasAssociation('cell')) {
            try {
                $cell = $this->propertyAccessor->getValue($entity, 'cell');
                if ($cell) {
                    return $this->findWebsite($cell, $em);
                }
            } catch (\Exception $e) {}
        }

        // Try 'media' property for MediaRelationIntl
        if ($metadata->hasAssociation('media')) {
            try {
                $media = $this->propertyAccessor->getValue($entity, 'media');
                if ($media) {
                    return $this->findWebsite($media, $em);
                }
            } catch (\Exception $e) {}
        }

        return null;
    }
}
