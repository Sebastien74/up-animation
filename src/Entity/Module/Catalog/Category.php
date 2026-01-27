<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Catalog\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Category.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_category')]
#[ORM\Index(columns: ['slug'], flags: ['fulltext'])]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Category extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'catalogcategory',
        'buttons' => [
            'subCategories' => 'admin_catalogsubcategory_index',
        ],
    ];
    protected static array $labels = [
        'admin_catalogsubcategory_index' => 'Sous-catégorie',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $promote = false;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $mainMediaInHeader = true;

    #[ORM\OneToMany(targetEntity: SubCategory::class, mappedBy: 'catalogcategory', cascade: ['persist', 'remove'])]
    private ArrayCollection|PersistentCollection $subCategories;

    #[ORM\OneToMany(targetEntity: CategoryMediaRelation::class, mappedBy: 'category', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: CategoryIntl::class, mappedBy: 'category', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->subCategories = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function isPromote(): ?bool
    {
        return $this->promote;
    }

    public function setPromote(bool $promote): static
    {
        $this->promote = $promote;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function isMainMediaInHeader(): ?bool
    {
        return $this->mainMediaInHeader;
    }

    public function setMainMediaInHeader(bool $mainMediaInHeader): static
    {
        $this->mainMediaInHeader = $mainMediaInHeader;

        return $this;
    }

    /**
     * @return Collection<int, SubCategory>
     */
    public function getSubCategories(): Collection
    {
        return $this->subCategories;
    }

    public function addSubCategory(SubCategory $subCategory): static
    {
        if (!$this->subCategories->contains($subCategory)) {
            $this->subCategories->add($subCategory);
            $subCategory->setCatalogcategory($this);
        }

        return $this;
    }

    public function removeSubCategory(SubCategory $subCategory): static
    {
        if ($this->subCategories->removeElement($subCategory)) {
            // set the owning side to null (unless already changed)
            if ($subCategory->getCatalogcategory() === $this) {
                $subCategory->setCatalogcategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CategoryMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(CategoryMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setCategory($this);
        }

        return $this;
    }

    public function removeMediaRelation(CategoryMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getCategory() === $this) {
                $mediaRelation->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CategoryIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(CategoryIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setCategory($this);
        }

        return $this;
    }

    public function removeIntl(CategoryIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getCategory() === $this) {
                $intl->setCategory(null);
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
}
