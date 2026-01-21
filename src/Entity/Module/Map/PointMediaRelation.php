<?php

declare(strict_types=1);

namespace App\Entity\Module\Map;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Map\PointMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * PointMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_map_point_media_relations')]
#[ORM\Entity(repositoryClass: PointMediaRelationRepository::class)]
class PointMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Point::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Point $point = null;

    public function getPoint(): ?Point
    {
        return $this->point;
    }

    public function setPoint(?Point $point): static
    {
        $this->point = $point;

        return $this;
    }
}
