<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;

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
        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if (ClassMetadata::MANY_TO_MANY == $mapping['type'] && $mapping['isOwningSide']) {
                $mappedTableName = $mapping['joinTable']['name'];
                $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix.'_'.$mappedTableName;
            }
        }
    }
}
