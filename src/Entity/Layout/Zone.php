<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Repository\Layout\ZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Zone.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_zone')]
#[ORM\Entity(repositoryClass: ZoneRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Zone extends BaseConfiguration
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'layout';
    protected static array $interface = [
        'name' => 'zone',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $titlePosition = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $containerSize = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asSection = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $backgroundFixed = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $backgroundFullSize = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $backgroundParallax = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $standardizeMedia = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $centerCol = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $colToRight = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $colToEnd = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $grid = [];

    #[ORM\OneToMany(targetEntity: ZoneIntl::class, mappedBy: 'zone', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\OneToMany(targetEntity: ZoneMediaRelation::class, mappedBy: 'zone', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\ManyToOne(targetEntity: Layout::class, cascade: ['persist'], inversedBy: 'zones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Layout $layout = null;

    #[ORM\OneToMany(targetEntity: Col::class, mappedBy: 'zone', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $cols;

    /**
     * Zone constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->cols = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $setDefault = !empty($_SERVER['REQUEST_URI']) && !str_contains($_SERVER['REQUEST_URI'], 'duplicate');

        if ($setDefault) {
            $this->paddingTop = !$this->paddingTop ? 'pt-lg' : $this->paddingTop;
            $this->paddingRight = !$this->paddingRight ? 'pe-0' : $this->paddingRight;
            $this->paddingBottom = !$this->paddingBottom ? 'pb-lg' : $this->paddingBottom;
            $this->paddingLeft = !$this->paddingLeft ? 'ps-0' : $this->paddingLeft;
            $this->paddingBottomMobile = !$this->paddingBottomMobile ? 'pb-0' : $this->paddingBottomMobile;
            $this->paddingBottomTablet = !$this->paddingBottomTablet ? 'pb-0' : $this->paddingBottomTablet;
        }

        parent::prePersist();
    }

    public function getTitlePosition(): ?string
    {
        return $this->titlePosition;
    }

    public function setTitlePosition(?string $titlePosition): static
    {
        $this->titlePosition = $titlePosition;

        return $this;
    }

    public function getContainerSize(): ?int
    {
        return $this->containerSize;
    }

    public function setContainerSize(?int $containerSize): static
    {
        $this->containerSize = $containerSize;

        return $this;
    }

    public function isAsSection(): ?bool
    {
        return $this->asSection;
    }

    public function setAsSection(bool $asSection): static
    {
        $this->asSection = $asSection;

        return $this;
    }

    public function isBackgroundFixed(): ?bool
    {
        return $this->backgroundFixed;
    }

    public function setBackgroundFixed(bool $backgroundFixed): static
    {
        $this->backgroundFixed = $backgroundFixed;

        return $this;
    }

    public function isBackgroundFullSize(): ?bool
    {
        return $this->backgroundFullSize;
    }

    public function setBackgroundFullSize(bool $backgroundFullSize): static
    {
        $this->backgroundFullSize = $backgroundFullSize;

        return $this;
    }

    public function isBackgroundParallax(): ?bool
    {
        return $this->backgroundParallax;
    }

    public function setBackgroundParallax(bool $backgroundParallax): static
    {
        $this->backgroundParallax = $backgroundParallax;

        return $this;
    }

    public function isStandardizeMedia(): ?bool
    {
        return $this->standardizeMedia;
    }

    public function setStandardizeMedia(bool $standardizeMedia): static
    {
        $this->standardizeMedia = $standardizeMedia;

        return $this;
    }

    public function isCenterCol(): ?bool
    {
        return $this->centerCol;
    }

    public function setCenterCol(bool $centerCol): static
    {
        $this->centerCol = $centerCol;

        return $this;
    }

    public function isColToRight(): ?bool
    {
        return $this->colToRight;
    }

    public function setColToRight(bool $colToRight): static
    {
        $this->colToRight = $colToRight;

        return $this;
    }

    public function isColToEnd(): ?bool
    {
        return $this->colToEnd;
    }

    public function setColToEnd(bool $colToEnd): static
    {
        $this->colToEnd = $colToEnd;

        return $this;
    }

    public function getGrid(): ?array
    {
        return $this->grid;
    }

    public function setGrid(?array $grid): static
    {
        $this->grid = $grid;

        return $this;
    }

    /**
     * @return Collection<int, ZoneIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(ZoneIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setZone($this);
        }

        return $this;
    }

    public function removeIntl(ZoneIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getZone() === $this) {
                $intl->setZone(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ZoneMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(ZoneMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setZone($this);
        }

        return $this;
    }

    public function removeMediaRelation(ZoneMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getZone() === $this) {
                $mediaRelation->setZone(null);
            }
        }

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
     * @return Collection<int, Col>
     */
    public function getCols(): Collection
    {
        return $this->cols;
    }

    public function addCol(Col $col): static
    {
        if (!$this->cols->contains($col)) {
            $this->cols->add($col);
            $col->setZone($this);
        }

        return $this;
    }

    public function removeCol(Col $col): static
    {
        if ($this->cols->removeElement($col)) {
            // set the owning side to null (unless already changed)
            if ($col->getZone() === $this) {
                $col->setZone(null);
            }
        }

        return $this;
    }
}
