<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Catalog\ProductMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProductMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_product_media_relations')]
#[ORM\Entity(repositoryClass: ProductMediaRelationRepository::class)]
class ProductMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Product::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Product $product = null;

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }
}