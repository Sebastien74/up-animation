<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseIntl;
use App\Repository\Module\Catalog\LotIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * LotIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_product_lot_intls')]
#[ORM\Entity(repositoryClass: LotIntlRepository::class)]
class LotIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Lot::class, cascade: ['persist'], inversedBy: 'intls')]
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
