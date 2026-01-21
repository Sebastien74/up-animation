<?php

declare(strict_types=1);

namespace App\Entity\Module\Gallery;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Gallery\CategoryRepository;
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
#[ORM\Table(name: 'module_gallery_category')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Category extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'gallerycategory',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asDefault = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayCategory = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $scrollInfinite = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $formatDate = 'dd MMM Y';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $itemsPerGallery = 24;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Gallery::class, cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $galleries;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CategoryMediaRelation::class, cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CategoryIntl::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
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
        $this->galleries = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
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

    public function isDisplayCategory(): ?bool
    {
        return $this->displayCategory;
    }

    public function setDisplayCategory(bool $displayCategory): static
    {
        $this->displayCategory = $displayCategory;

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

    public function getFormatDate(): ?string
    {
        return $this->formatDate;
    }

    public function setFormatDate(?string $formatDate): static
    {
        $this->formatDate = $formatDate;

        return $this;
    }

    public function getItemsPerGallery(): ?int
    {
        return $this->itemsPerGallery;
    }

    public function setItemsPerGallery(?int $itemsPerGallery): static
    {
        $this->itemsPerGallery = $itemsPerGallery;

        return $this;
    }

    /**
     * @return Collection<int, Gallery>
     */
    public function getGalleries(): Collection
    {
        return $this->galleries;
    }

    public function addGallery(Gallery $gallery): static
    {
        if (!$this->galleries->contains($gallery)) {
            $this->galleries->add($gallery);
            $gallery->setCategory($this);
        }

        return $this;
    }

    public function removeGallery(Gallery $gallery): static
    {
        if ($this->galleries->removeElement($gallery)) {
            // set the owning side to null (unless already changed)
            if ($gallery->getCategory() === $this) {
                $gallery->setCategory(null);
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
