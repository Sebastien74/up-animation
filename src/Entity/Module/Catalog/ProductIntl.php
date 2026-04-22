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
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $introductionTitle = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $bodyTitle = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $bodyTitleSecond = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $bodySecond = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $advendisingTitle = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $advendisingTitleFirst = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $advendisingFirst = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $advendisingTitleSecond = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $advendisingSecond = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $advendisingTitleThird = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $advendisingThird = null;

    #[ORM\ManyToOne(targetEntity: Product::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Product $product = null;

    public function getIntroductionTitle(): ?string
    {
        return $this->introductionTitle;
    }

    public function setIntroductionTitle(?string $introductionTitle): static
    {
        $this->introductionTitle = $introductionTitle;

        return $this;
    }

    public function getBodyTitle(): ?string
    {
        return $this->bodyTitle;
    }

    public function setBodyTitle(?string $bodyTitle): static
    {
        $this->bodyTitle = $bodyTitle;

        return $this;
    }

    public function getBodyTitleSecond(): ?string
    {
        return $this->bodyTitleSecond;
    }

    public function setBodyTitleSecond(?string $bodyTitleSecond): static
    {
        $this->bodyTitleSecond = $bodyTitleSecond;

        return $this;
    }

    public function getBodySecond(): ?string
    {
        return $this->bodySecond;
    }

    public function setBodySecond(?string $bodySecond): static
    {
        $this->bodySecond = $bodySecond;

        return $this;
    }

    public function getAdvendisingTitle(): ?string
    {
        return $this->advendisingTitle;
    }

    public function setAdvendisingTitle(?string $advendisingTitle): static
    {
        $this->advendisingTitle = $advendisingTitle;

        return $this;
    }

    public function getAdvendisingTitleFirst(): ?string
    {
        return $this->advendisingTitleFirst;
    }

    public function setAdvendisingTitleFirst(?string $advendisingTitleFirst): static
    {
        $this->advendisingTitleFirst = $advendisingTitleFirst;

        return $this;
    }

    public function getAdvendisingFirst(): ?string
    {
        return $this->advendisingFirst;
    }

    public function setAdvendisingFirst(?string $advendisingFirst): static
    {
        $this->advendisingFirst = $advendisingFirst;

        return $this;
    }

    public function getAdvendisingTitleSecond(): ?string
    {
        return $this->advendisingTitleSecond;
    }

    public function setAdvendisingTitleSecond(?string $advendisingTitleSecond): static
    {
        $this->advendisingTitleSecond = $advendisingTitleSecond;

        return $this;
    }

    public function getAdvendisingSecond(): ?string
    {
        return $this->advendisingSecond;
    }

    public function setAdvendisingSecond(?string $advendisingSecond): static
    {
        $this->advendisingSecond = $advendisingSecond;

        return $this;
    }

    public function getAdvendisingTitleThird(): ?string
    {
        return $this->advendisingTitleThird;
    }

    public function setAdvendisingTitleThird(?string $advendisingTitleThird): static
    {
        $this->advendisingTitleThird = $advendisingTitleThird;

        return $this;
    }

    public function getAdvendisingThird(): ?string
    {
        return $this->advendisingThird;
    }

    public function setAdvendisingThird(?string $advendisingThird): static
    {
        $this->advendisingThird = $advendisingThird;

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
