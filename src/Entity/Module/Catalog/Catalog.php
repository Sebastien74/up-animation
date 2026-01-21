<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Entity\Layout\Layout;
use App\Repository\Module\Catalog\CatalogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Catalog.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog')]
#[ORM\Entity(repositoryClass: CatalogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Catalog extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'catalog',
        'resize' => true,
        'buttons' => [
            'products' => 'admin_catalogproduct_index',
        ],
        'seo' => [
            'intl.title',
        ],
    ];
    protected static array $labels = [
        'admin_catalogproduct_index' => 'Produits',
        'intl.title' => 'Titre',
        'intl.introduction' => 'Introduction',
        'intl.body' => 'Description',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $formatDate = 'dd MMM Y';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected array $tabs = ['intls', 'categories', 'features', 'configuration', 'products', 'seo', 'medias'];

    #[ORM\OneToOne(targetEntity: Layout::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'layout_id', referencedColumnName: 'id')]
    private ?Layout $layout = null;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'catalog', cascade: ['persist'])]
    #[ORM\OrderBy(['adminName' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $products;

    #[ORM\OneToMany(targetEntity: CatalogMediaRelation::class, mappedBy: 'catalog', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: CatalogIntl::class, mappedBy: 'catalog', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Catalog constructor.
     */
    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function getFormatDate(): ?string
    {
        return $this->formatDate;
    }

    public function setFormatDate(?string $formatDate): static
    {
        $this->formatDate = $formatDate;

        return $this;
    }

    public function getTabs(): ?array
    {
        return $this->tabs;
    }

    public function setTabs(?array $tabs): static
    {
        $this->tabs = $tabs;

        return $this;
    }

    public function getLayout(): ?Layout
    {
        return $this->layout;
    }

    public function setLayout(?Layout $layout): static
    {
        $this->layout = $layout;

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
            $product->setCatalog($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getCatalog() === $this) {
                $product->setCatalog(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CatalogMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(CatalogMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setCatalog($this);
        }

        return $this;
    }

    public function removeMediaRelation(CatalogMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getCatalog() === $this) {
                $mediaRelation->setCatalog(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CatalogIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(CatalogIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setCatalog($this);
        }

        return $this;
    }

    public function removeIntl(CatalogIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getCatalog() === $this) {
                $intl->setCatalog(null);
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
