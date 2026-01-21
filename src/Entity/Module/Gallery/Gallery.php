<?php

declare(strict_types=1);

namespace App\Entity\Module\Gallery;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Gallery\GalleryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Gallery.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_gallery')]
#[ORM\Entity(repositoryClass: GalleryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Gallery extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'gallery',
        'indexPage' => 'category',
    ];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $nbrColumn = 3;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $popup = true;

    #[ORM\OneToMany(mappedBy: 'gallery', targetEntity: GalleryMediaRelation::class, cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\ManyToOne(targetEntity: Category::class, cascade: ['persist'], inversedBy: 'galleries')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Gallery constructor.
     */
    public function __construct()
    {
        $this->mediaRelations = new ArrayCollection();
    }

    public function getNbrColumn(): ?int
    {
        return $this->nbrColumn;
    }

    public function setNbrColumn(?int $nbrColumn): static
    {
        $this->nbrColumn = $nbrColumn;

        return $this;
    }

    public function isPopup(): ?bool
    {
        return $this->popup;
    }

    public function setPopup(bool $popup): static
    {
        $this->popup = $popup;

        return $this;
    }

    /**
     * @return Collection<int, GalleryMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(GalleryMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setGallery($this);
        }

        return $this;
    }

    public function removeMediaRelation(GalleryMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getGallery() === $this) {
                $mediaRelation->setGallery(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

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
