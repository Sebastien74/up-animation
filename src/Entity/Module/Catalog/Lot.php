<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Repository\Module\Catalog\LotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Lot.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_product_lot')]
#[ORM\Entity(repositoryClass: LotRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Lot extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'product';
    protected static array $interface = [
        'name' => 'cataloglot',
    ];

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $reference = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $type = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    protected ?float $surface = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    protected ?float $balconySurface = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    protected ?float $price = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $sold = false;

    #[ORM\OneToMany(targetEntity: LotMediaRelation::class, mappedBy: 'lot', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: LotIntl::class, mappedBy: 'lot', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'lots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    /**
     * Lot constructor.
     */
    public function __construct()
    {
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(?float $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    public function getBalconySurface(): ?float
    {
        return $this->balconySurface;
    }

    public function setBalconySurface(?float $balconySurface): static
    {
        $this->balconySurface = $balconySurface;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isSold(): ?bool
    {
        return $this->sold;
    }

    public function setSold(bool $sold): static
    {
        $this->sold = $sold;

        return $this;
    }

    /**
     * @return Collection<int, LotMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(LotMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setLot($this);
        }

        return $this;
    }

    public function removeMediaRelation(LotMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getLot() === $this) {
                $mediaRelation->setLot(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LotIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(LotIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setLot($this);
        }

        return $this;
    }

    public function removeIntl(LotIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getLot() === $this) {
                $intl->setLot(null);
            }
        }

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
