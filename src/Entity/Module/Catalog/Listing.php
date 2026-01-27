<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Repository\Module\Catalog\ListingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Listing.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_listing')]
#[ORM\Entity(repositoryClass: ListingRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'catalogs',
        joinColumns: [new ORM\JoinColumn(name: 'listing_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'catalog_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_catalog_listing_catalogs')
    ),
    new ORM\AssociationOverride(
        name: 'categories',
        joinColumns: [new ORM\JoinColumn(name: 'listing_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_catalog_listings_categories')
    ),
    new ORM\AssociationOverride(
        name: 'subCategories',
        joinColumns: [new ORM\JoinColumn(name: 'listing_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'sub_category_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_catalog_listings_sub_categories')
    ),
    new ORM\AssociationOverride(
        name: 'features',
        joinColumns: [new ORM\JoinColumn(name: 'listing_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'feature_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_catalog_listings_features')
    ),
])]
class Listing extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'cataloglisting',
    ];

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $display = 'disable';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $searchText = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $combineFieldsText = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $counter = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayLabel = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $updateFields = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $groupByCategories = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $scrollInfinite = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $showMoreBtn = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $showMap = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $searchCatalogs = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $searchCategories = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $searchSubCategories = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $searchFeatures = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $searchProducts = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $orderBy = 'position';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $orderSort = 'ASC';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $itemsPerPage = 12;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iconBackground = null;

    #[ORM\OneToMany(targetEntity: ListingFeatureValue::class, mappedBy: 'listing', cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $featuresValues;

    #[ORM\OneToMany(targetEntity: ListingIntl::class, mappedBy: 'listing', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Page $page = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\ManyToMany(targetEntity: Catalog::class, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    private ArrayCollection|PersistentCollection $catalogs;

    #[ORM\ManyToMany(targetEntity: Category::class, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    private ArrayCollection|PersistentCollection $categories;

    #[ORM\ManyToMany(targetEntity: SubCategory::class, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    private ArrayCollection|PersistentCollection $subCategories;

    #[ORM\ManyToMany(targetEntity: Feature::class, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $features;

    #[ORM\ManyToMany(targetEntity: Product::class, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    private ArrayCollection|PersistentCollection $products;

    /**
     * Listing constructor.
     */
    public function __construct()
    {
        $this->featuresValues = new ArrayCollection();
        $this->intls = new ArrayCollection();
        $this->catalogs = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->subCategories = new ArrayCollection();
        $this->features = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getDisplay(): ?string
    {
        return $this->display;
    }

    public function setDisplay(string $display): static
    {
        $this->display = $display;

        return $this;
    }

    public function isSearchText(): ?bool
    {
        return $this->searchText;
    }

    public function setSearchText(bool $searchText): static
    {
        $this->searchText = $searchText;

        return $this;
    }

    public function isCombineFieldsText(): ?bool
    {
        return $this->combineFieldsText;
    }

    public function setCombineFieldsText(bool $combineFieldsText): static
    {
        $this->combineFieldsText = $combineFieldsText;

        return $this;
    }

    public function isCounter(): ?bool
    {
        return $this->counter;
    }

    public function setCounter(bool $counter): static
    {
        $this->counter = $counter;

        return $this;
    }

    public function isDisplayLabel(): ?bool
    {
        return $this->displayLabel;
    }

    public function setDisplayLabel(bool $displayLabel): static
    {
        $this->displayLabel = $displayLabel;

        return $this;
    }

    public function isUpdateFields(): ?bool
    {
        return $this->updateFields;
    }

    public function setUpdateFields(bool $updateFields): static
    {
        $this->updateFields = $updateFields;

        return $this;
    }

    public function isGroupByCategories(): ?bool
    {
        return $this->groupByCategories;
    }

    public function setGroupByCategories(bool $groupByCategories): static
    {
        $this->groupByCategories = $groupByCategories;

        return $this;
    }

    public function isScrollInfinite(): ?bool
    {
        return $this->scrollInfinite;
    }

    public function setScrollInfinite(bool $scrollInfinite): static
    {
        $this->scrollInfinite = $scrollInfinite;

        return $this;
    }

    public function isShowMoreBtn(): ?bool
    {
        return $this->showMoreBtn;
    }

    public function setShowMoreBtn(bool $showMoreBtn): static
    {
        $this->showMoreBtn = $showMoreBtn;

        return $this;
    }

    public function isShowMap(): ?bool
    {
        return $this->showMap;
    }

    public function setShowMap(bool $showMap): static
    {
        $this->showMap = $showMap;

        return $this;
    }

    public function getSearchCatalogs(): ?string
    {
        return $this->searchCatalogs;
    }

    public function setSearchCatalogs(?string $searchCatalogs): static
    {
        $this->searchCatalogs = $searchCatalogs;

        return $this;
    }

    public function getSearchCategories(): ?string
    {
        return $this->searchCategories;
    }

    public function setSearchCategories(?string $searchCategories): static
    {
        $this->searchCategories = $searchCategories;

        return $this;
    }

    public function getSearchSubCategories(): ?string
    {
        return $this->searchSubCategories;
    }

    public function setSearchSubCategories(?string $searchSubCategories): static
    {
        $this->searchSubCategories = $searchSubCategories;

        return $this;
    }

    public function getSearchFeatures(): ?string
    {
        return $this->searchFeatures;
    }

    public function setSearchFeatures(?string $searchFeatures): static
    {
        $this->searchFeatures = $searchFeatures;

        return $this;
    }

    public function getSearchProducts(): ?string
    {
        return $this->searchProducts;
    }

    public function setSearchProducts(?string $searchProducts): static
    {
        $this->searchProducts = $searchProducts;

        return $this;
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    public function setOrderBy(string $orderBy): static
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getOrderSort(): ?string
    {
        return $this->orderSort;
    }

    public function setOrderSort(string $orderSort): static
    {
        $this->orderSort = $orderSort;

        return $this;
    }

    public function getItemsPerPage(): ?int
    {
        return $this->itemsPerPage;
    }

    public function setItemsPerPage(?int $itemsPerPage): static
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIconBackground(): ?string
    {
        return $this->iconBackground;
    }

    public function setIconBackground(?string $iconBackground): static
    {
        $this->iconBackground = $iconBackground;

        return $this;
    }

    /**
     * @return Collection<int, ListingFeatureValue>
     */
    public function getFeaturesValues(): Collection
    {
        return $this->featuresValues;
    }

    public function addFeaturesValue(ListingFeatureValue $featuresValue): static
    {
        if (!$this->featuresValues->contains($featuresValue)) {
            $this->featuresValues->add($featuresValue);
            $featuresValue->setListing($this);
        }

        return $this;
    }

    public function removeFeaturesValue(ListingFeatureValue $featuresValue): static
    {
        if ($this->featuresValues->removeElement($featuresValue)) {
            // set the owning side to null (unless already changed)
            if ($featuresValue->getListing() === $this) {
                $featuresValue->setListing(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ListingIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(ListingIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setListing($this);
        }

        return $this;
    }

    public function removeIntl(ListingIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getListing() === $this) {
                $intl->setListing(null);
            }
        }

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;

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

    /**
     * @return Collection<int, Feature>
     */
    public function getFeatures(): Collection
    {
        return $this->features;
    }

    public function addFeature(Feature $feature): static
    {
        if (!$this->features->contains($feature)) {
            $this->features->add($feature);
        }

        return $this;
    }

    public function removeFeature(Feature $feature): static
    {
        $this->features->removeElement($feature);

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        $this->products->removeElement($product);

        return $this;
    }
}
