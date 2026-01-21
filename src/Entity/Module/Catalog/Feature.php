<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Catalog\FeatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Feature.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_feature')]
#[ORM\Entity(repositoryClass: FeatureRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'catalogs',
        joinColumns: [new ORM\JoinColumn(name: 'feature_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'catalog_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_catalog_feature_catalogs')
    ),
])]
class Feature extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'catalogfeature',
        'buttons' => [
            'values' => 'admin_catalogfeaturevalue_index',
        ],
    ];
    protected static array $labels = [
        'admin_catalogfeaturevalue_index' => 'Valeurs',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asBool = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iconClass = null;

    #[ORM\OneToMany(targetEntity: FeatureValue::class, mappedBy: 'catalogfeature', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $values;

    #[ORM\OneToMany(targetEntity: FeatureMediaRelation::class, mappedBy: 'feature', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: FeatureIntl::class, mappedBy: 'feature', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\ManyToMany(targetEntity: Catalog::class, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $catalogs;

    /**
     * Feature constructor.
     */
    public function __construct()
    {
        $this->values = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
        $this->catalogs = new ArrayCollection();
    }

    public function isAsBool(): ?bool
    {
        return $this->asBool;
    }

    public function setAsBool(bool $asBool): static
    {
        $this->asBool = $asBool;

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
     * @return Collection<int, FeatureValue>
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    public function addValue(FeatureValue $value): static
    {
        if (!$this->values->contains($value)) {
            $this->values->add($value);
            $value->setCatalogfeature($this);
        }

        return $this;
    }

    public function removeValue(FeatureValue $value): static
    {
        if ($this->values->removeElement($value)) {
            // set the owning side to null (unless already changed)
            if ($value->getCatalogfeature() === $this) {
                $value->setCatalogfeature(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FeatureMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(FeatureMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setFeature($this);
        }

        return $this;
    }

    public function removeMediaRelation(FeatureMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getFeature() === $this) {
                $mediaRelation->setFeature(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FeatureIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(FeatureIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setFeature($this);
        }

        return $this;
    }

    public function removeIntl(FeatureIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getFeature() === $this) {
                $intl->setFeature(null);
            }
        }

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
