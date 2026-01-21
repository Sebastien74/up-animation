<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Catalog\FeatureValueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * FeatureValue.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_feature_value')]
#[ORM\Index(columns: ['slug'], flags: ['fulltext'])]
#[ORM\Entity(repositoryClass: FeatureValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'catalogs',
        joinColumns: [new ORM\JoinColumn(name: 'value_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'catalog_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_catalog_feature_value_catalogs')
    ),
])]
class FeatureValue extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'catalogfeature';
    protected static array $interface = [
        'name' => 'catalogfeaturevalue',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isCustomized = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iconClass = null;

    #[ORM\OneToMany(targetEntity: FeatureValueMediaRelation::class, mappedBy: 'featureValue', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: FeatureValueIntl::class, mappedBy: 'featureValue', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Feature::class, inversedBy: 'values')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Feature $catalogfeature = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product = null;

    #[ORM\ManyToMany(targetEntity: Catalog::class, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $catalogs;

    /**
     * FeatureValue constructor.
     */
    public function __construct()
    {
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
        $this->catalogs = new ArrayCollection();
    }

    public function isCustomized(): ?bool
    {
        return $this->isCustomized;
    }

    public function setCustomized(bool $isCustomized): static
    {
        $this->isCustomized = $isCustomized;

        return $this;
    }

    public function getIconClass(): ?string
    {
        return $this->iconClass;
    }

    public function setIconClass(?string $iconClass): static
    {
        $this->iconClass = $iconClass;

        return $this;
    }

    /**
     * @return Collection<int, FeatureValueMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(FeatureValueMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setFeatureValue($this);
        }

        return $this;
    }

    public function removeMediaRelation(FeatureValueMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getFeatureValue() === $this) {
                $mediaRelation->setFeatureValue(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FeatureValueIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(FeatureValueIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setFeatureValue($this);
        }

        return $this;
    }

    public function removeIntl(FeatureValueIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getFeatureValue() === $this) {
                $intl->setFeatureValue(null);
            }
        }

        return $this;
    }

    public function getCatalogfeature(): ?Feature
    {
        return $this->catalogfeature;
    }

    public function setCatalogfeature(?Feature $catalogfeature): static
    {
        $this->catalogfeature = $catalogfeature;

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

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

    public function setIsCustomized(bool $isCustomized): static
    {
        $this->isCustomized = $isCustomized;

        return $this;
    }

    /**
     * @return Collection<int, Catalog>
     */
    public function getCatalogs(): Collection
    {
        return $this->catalogs;
    }

    public function addCatalog(Catalog $catalog): static
    {
        if (!$this->catalogs->contains($catalog)) {
            $this->catalogs->add($catalog);
        }

        return $this;
    }

    public function removeCatalog(Catalog $catalog): static
    {
        $this->catalogs->removeElement($catalog);

        return $this;
    }
}
