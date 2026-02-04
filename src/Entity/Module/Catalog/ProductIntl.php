<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseIntl;
use App\Repository\Module\Catalog\ProductIntlRepository;
use Doctrine\DBAL\Types\Types;
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
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $gather = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $sympathise = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $impress = null;

    #[ORM\ManyToOne(targetEntity: Product::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Product $product = null;

    public function getGather(): ?string
    {
        return $this->gather;
    }

    public function setGather(?string $gather): static
    {
        $this->gather = $gather;

        return $this;
    }

    public function getSympathise(): ?string
    {
        return $this->sympathise;
    }

    public function setSympathise(?string $sympathise): static
    {
        $this->sympathise = $sympathise;

        return $this;
    }

    public function getImpress(): ?string
    {
        return $this->impress;
    }

    public function setImpress(?string $impress): static
    {
        $this->impress = $impress;

        return $this;
    }

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
