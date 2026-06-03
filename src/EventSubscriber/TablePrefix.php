<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

/**
 * TablePrefix.
 *
 * Add prefix on DB tables
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsDoctrineListener(event: Events::loadClassMetadata)]
class TablePrefix
{
    /**
     * TablePrefix constructor.
     */
    public function __construct(protected string $prefix = '')
    {
    }

    /**
     * Load.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata = $eventArgs->getClassMetadata();
        if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->getName() === $classMetadata->rootEntityName) {
            $classMetadata->setPrimaryTable([
                'name' => $this->prefix.'_'.$classMetadata->getTableName(),
            ]);
        }
        foreach ($classMetadata->getAssociationMappings() as $mapping) {
            if ($mapping->isManyToManyOwningSide()) {
                $mapping->joinTable->name = $this->prefix.'_'.$mapping->joinTable->name;
            }
        }
    }
}
