<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseIntl;
use App\Repository\Module\Catalog\ProductIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProductIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_product_intls')]
#[ORM\Entity(repositoryClass: ProductIntlRepository::class)]
class ProductIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Product::class, cascade: ['persist'], inversedBy: 'intls')]
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
