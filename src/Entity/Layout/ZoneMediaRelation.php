<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseMediaRelation;
use App\Repository\Layout\ZoneMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ZoneMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_zone_media_relations')]
#[ORM\Entity(repositoryClass: ZoneMediaRelationRepository::class)]
class ZoneMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Zone::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Zone $zone = null;

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): static
    {
        $this->zone = $zone;

        return $this;
    }
}
