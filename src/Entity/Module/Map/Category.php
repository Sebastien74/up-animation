<?php

declare(strict_types=1);

namespace App\Entity\Module\Map;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Map\CategoryRepository;
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
#[ORM\Table(name: 'module_map_category')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Category extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'mapcategory',
    ];

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $marker = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $markerWidth = 30;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $markerHeight = 30;

    #[ORM\OneToMany(targetEntity: CategoryIntl::class, mappedBy: 'category', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Point constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
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

    public function getMarkerWidth(): ?int
    {
        return $this->markerWidth;
    }

    public function setMarkerWidth(?int $markerWidth): static
    {
        $this->markerWidth = $markerWidth;

        return $this;
    }

    public function getMarkerHeight(): ?int
    {
        return $this->markerHeight;
    }

    public function setMarkerHeight(?int $markerHeight): static
    {
        $this->markerHeight = $markerHeight;

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
