<?php

declare(strict_types=1);

namespace App\Entity\Module\Map;

use App\Entity\BaseEntity;
use App\Entity\Information\Phone;
use App\Repository\Module\Map\PointRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Point.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_map_point')]
#[ORM\Entity(repositoryClass: PointRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'categories',
        joinColumns: [new ORM\JoinColumn(name: 'point_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_map_point_categories')
    ),
    new ORM\AssociationOverride(
        name: 'phones',
        joinColumns: [new ORM\JoinColumn(name: 'point_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'phone_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_map_point_phones')
    ),
])]
class Point extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'map';
    protected static array $interface = [
        'name' => 'mappoint',
        'prePersistTitle' => false,
    ];

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $marker = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hide = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $countries = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $departments = [];

    #[ORM\OneToOne(targetEntity: Address::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Address $address = null;

    #[ORM\OneToOne(targetEntity: PointGeoJson::class, inversedBy: 'point', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'geo_json_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Assert\Valid(groups: ['form_submission'])]
    private ?PointGeoJson $geoJson = null;

    #[ORM\OneToMany(targetEntity: PointMediaRelation::class, mappedBy: 'point', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: PointIntl::class, mappedBy: 'point', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Map::class, cascade: ['persist'], inversedBy: 'points')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Map $map = null;

    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $categories;

    #[ORM\ManyToMany(targetEntity: Phone::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    private ArrayCollection|PersistentCollection $phones;

    /**
     * Point constructor.
     */
    public function __construct()
    {
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->phones = new ArrayCollection();
    }

    public function getMarker(): ?string
    {
        return $this->marker;
    }

    public function setMarker(?string $marker): static
    {
        $this->marker = $marker;

        return $this;
    }

    public function isHide(): ?bool
    {
        return $this->hide;
    }

    public function setHide(bool $hide): static
    {
        $this->hide = $hide;

        return $this;
    }

    public function getCountries(): ?array
    {
        return $this->countries;
    }

    public function setCountries(?array $countries): static
    {
        $this->countries = $countries;

        return $this;
    }

    public function getDepartments(): ?array
    {
        return $this->departments;
    }

    public function setDepartments(?array $departments): static
    {
        $this->departments = $departments;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getGeoJson(): ?PointGeoJson
    {
        return $this->geoJson;
    }

    public function setGeoJson(?PointGeoJson $geoJson): static
    {
        $this->geoJson = $geoJson;

        return $this;
    }

    /**
     * @return Collection<int, PointMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(PointMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setPoint($this);
        }

        return $this;
    }

    public function removeMediaRelation(PointMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getPoint() === $this) {
                $mediaRelation->setPoint(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PointIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(PointIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setPoint($this);
        }

        return $this;
    }

    public function removeIntl(PointIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getPoint() === $this) {
                $intl->setPoint(null);
            }
        }

        return $this;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setMap(?Map $map): static
    {
        $this->map = $map;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, Phone>
     */
    public function getPhones(): Collection
    {
        return $this->phones;
    }

    public function addPhone(Phone $phone): static
    {
        if (!$this->phones->contains($phone)) {
            $this->phones->add($phone);
        }

        return $this;
    }

    public function removePhone(Phone $phone): static
    {
        $this->phones->removeElement($phone);

        return $this;
    }
}
