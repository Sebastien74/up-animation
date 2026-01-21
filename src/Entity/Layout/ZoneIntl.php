<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseIntl;
use App\Repository\Layout\ZoneIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ZoneIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_zone_intls')]
#[ORM\Entity(repositoryClass: ZoneIntlRepository::class)]
class ZoneIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Zone::class, cascade: ['persist'], inversedBy: 'intls')]
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