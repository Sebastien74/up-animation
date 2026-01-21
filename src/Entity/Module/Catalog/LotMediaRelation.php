<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Catalog\LotMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * LotMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_product_lot_media_relations')]
#[ORM\Entity(repositoryClass: LotMediaRelationRepository::class)]
class LotMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Lot::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Lot $lot = null;

    public function getLot(): ?Lot
    {
        return $this->lot;
    }

    public function setLot(?Lot $lot): static
    {
        $this->lot = $lot;

        return $this;
    }
}
