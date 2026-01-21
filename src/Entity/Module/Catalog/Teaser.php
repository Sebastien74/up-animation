<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseTeaser;
use App\Repository\Module\Catalog\TeaserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Teaser.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_product_teaser')]
#[ORM\Entity(repositoryClass: TeaserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'catalogs',
        joinColumns: [new ORM\JoinColumn(name: 'teaser_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'catalog_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_catalog_product_teaser_catalogs')
    ),
    new ORM\AssociationOverride(
        name: 'categories',
        joinColumns: [new ORM\JoinColumn(name: 'teaser_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_catalog_product_teaser_categories')
    ),
])]
class Teaser extends BaseTeaser
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'productteaser',
        'prePersistTitle' => false,
    ];

    #[ORM\OneToMany(targetEntity: TeaserIntl::class, mappedBy: 'teaser', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToMany(targetEntity: Catalog::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $catalogs;

    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $categories;

    #[ORM\ManyToMany(targetEntity: SubCategory::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $subCategories;

    /**
     * Teaser constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
        $this->catalogs = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->subCategories = new ArrayCollection();
    }

    /**
     * @return Collection<int, TeaserIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(TeaserIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setTeaser($this);
        }

        return $this;
    }

    public function removeIntl(TeaserIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getTeaser() === $this) {
                $intl->setTeaser(null);
            }
        }

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
        }

        return $this;
    }

    public function removeSubCategory(SubCategory $subCategory): static
    {
        $this->subCategories->removeElement($subCategory);

        return $this;
    }
}
