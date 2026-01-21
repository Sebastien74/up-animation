<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Catalog\CatalogMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CatalogMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_media_relations')]
#[ORM\Entity(repositoryClass: CatalogMediaRelationRepository::class)]
class CatalogMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Catalog::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Catalog $catalog = null;

    public function getCatalog(): ?Catalog
    {
        return $this->catalog;
    }

    public function setCatalog(?Catalog $catalog): static
    {
        $this->catalog = $catalog;

        return $this;
    }
}
