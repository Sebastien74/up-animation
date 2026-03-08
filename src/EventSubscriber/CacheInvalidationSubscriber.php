<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Core\Website;
use App\Entity\BaseInterface;
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

        foreach ($entities as $entity) {
            if ($entity instanceof BaseInterface) {
                $website = $this->findWebsite($entity, $em);
                if ($website instanceof Website) {
                    $websitesToInvalidate[$website->getId()] = $website;
                }
            }
        }

        foreach ($websitesToInvalidate as $website) {
            $website->setCacheClearDate(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(Website::class), $website);
        }
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
        if (property_exists($class, 'masterField')) {
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

        return null;
    }
}
