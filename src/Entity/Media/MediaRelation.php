<?php

declare(strict_types=1);

namespace App\Entity\Media;

use App\Entity\BaseMediaRelation;
use App\Repository\Media\MediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * MediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'media_relation')]
#[ORM\Entity(repositoryClass: MediaRelationRepository::class)]
class MediaRelation extends BaseMediaRelation
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'mediarelation',
        'search' => true,
    ];
}
