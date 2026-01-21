<?php

declare(strict_types=1);

namespace App\Entity\Module\Map;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Map\PointGeoJsonRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * PointGeoJson.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_map_point_geo_json')]
#[ORM\Entity(repositoryClass: PointGeoJsonRepository::class)]
class PointGeoJson extends BaseMediaRelation
{
    #[ORM\OneToOne(targetEntity: Point::class, mappedBy: 'geoJson')]
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
