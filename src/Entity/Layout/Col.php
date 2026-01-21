<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Repository\Layout\ColRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Col.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_col')]
#[ORM\Entity(repositoryClass: ColRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Col extends BaseConfiguration
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'zone';
    protected static array $interface = [
        'name' => 'col',
    ];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $size = 12;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $mobileSize = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $tabletSize = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $miniPcSize = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $backgroundFullSize = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $backgroundFullHeight = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $sticky = false;

    #[ORM\ManyToOne(targetEntity: Zone::class, cascade: ['persist'], inversedBy: 'cols')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Zone $zone = null;

    #[ORM\OneToMany(targetEntity: Block::class, mappedBy: 'col', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $blocks;

    /**
     * Col constructor.
     */
    public function __construct()
    {
        $this->blocks = new ArrayCollection();
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getMobileSize(): ?int
    {
        return $this->mobileSize;
    }

    public function setMobileSize(?int $mobileSize): static
    {
        $this->mobileSize = $mobileSize;

        return $this;
    }

    public function getTabletSize(): ?int
    {
        return $this->tabletSize;
    }

    public function setTabletSize(?int $tabletSize): static
    {
        $this->tabletSize = $tabletSize;

        return $this;
    }

    public function getMiniPcSize(): ?int
    {
        return $this->miniPcSize;
    }

    public function setMiniPcSize(?int $miniPcSize): static
    {
        $this->miniPcSize = $miniPcSize;

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

    public function isBackgroundFullHeight(): ?bool
    {
        return $this->backgroundFullHeight;
    }

    public function setBackgroundFullHeight(bool $backgroundFullHeight): static
    {
        $this->backgroundFullHeight = $backgroundFullHeight;

        return $this;
    }

    public function isSticky(): ?bool
    {
        return $this->sticky;
    }

    public function setSticky(bool $sticky): static
    {
        $this->sticky = $sticky;

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): static
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * @return Collection<int, Block>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(Block $block): static
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks->add($block);
            $block->setCol($this);
        }

        return $this;
    }

    public function removeBlock(Block $block): static
    {
        if ($this->blocks->removeElement($block)) {
            // set the owning side to null (unless already changed)
            if ($block->getCol() === $this) {
                $block->setCol(null);
            }
        }

        return $this;
    }
}
