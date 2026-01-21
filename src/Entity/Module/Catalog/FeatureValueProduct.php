<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Repository\Module\Catalog\FeatureValueProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * FeatureValueProduct.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_product_values')]
#[ORM\Entity(repositoryClass: FeatureValueProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FeatureValueProduct extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'product';
    protected static array $interface = [
        'name' => 'catalogfeaturevalueproduct',
    ];

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $featurePosition = 0;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayInArray = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asDefault = false;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'values')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Feature::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Feature $feature = null;

    #[ORM\ManyToOne(targetEntity: FeatureValue::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FeatureValue $value = null;

    public function getFeaturePosition(): ?int
    {
        return $this->featurePosition;
    }

    public function setFeaturePosition(int $featurePosition): static
    {
        $this->featurePosition = $featurePosition;

        return $this;
    }

    public function isDisplayInArray(): ?bool
    {
        return $this->displayInArray;
    }

    public function setDisplayInArray(bool $displayInArray): static
    {
        $this->displayInArray = $displayInArray;

        return $this;
    }

    public function isAsDefault(): ?bool
    {
        return $this->asDefault;
    }

    public function setAsDefault(bool $asDefault): static
    {
        $this->asDefault = $asDefault;

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

    public function getFeature(): ?Feature
    {
        return $this->feature;
    }

    public function setFeature(?Feature $feature): static
    {
        $this->feature = $feature;

        return $this;
    }

    public function getValue(): ?FeatureValue
    {
        return $this->value;
    }

    public function setValue(?FeatureValue $value): static
    {
        $this->value = $value;

        return $this;
    }
}
