<?php

declare(strict_types=1);

namespace App\Entity\Module\Map;

use App\Entity\BaseIntl;
use App\Repository\Module\Map\PointIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * PointIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_map_point_intls')]
#[ORM\Entity(repositoryClass: PointIntlRepository::class)]
class PointIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Point::class, cascade: ['persist'], inversedBy: 'intls')]
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
