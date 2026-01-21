<?php

declare(strict_types=1);

namespace App\Entity\Module\Menu;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Menu\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Menu.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_menu')]
#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Menu extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'menu',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $main = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $footer = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $fixedOnScroll = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $alwaysFixed = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $dropdownHover = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $vertical = false;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    private string $template = 'bootstrap';

    #[ORM\Column(type: Types::STRING, length: 10)]
    #[Assert\NotBlank]
    private string $expand = 'lg';

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    private string $size = 'container';

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    private string $alignment = 'start';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxLevel = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', referencedColumnName: 'id', nullable: false)]
    private ?Website $website = null;

    #[ORM\OneToMany(targetEntity: Link::class, mappedBy: 'menu', cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $links;

    /**
     * Menu constructor.
     */
    public function __construct()
    {
        $this->links = new ArrayCollection();
    }

    public function isMain(): ?bool
    {
        return $this->main;
    }

    public function setMain(bool $main): static
    {
        $this->main = $main;

        return $this;
    }

    public function isFooter(): ?bool
    {
        return $this->footer;
    }

    public function setFooter(bool $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    public function isFixedOnScroll(): ?bool
    {
        return $this->fixedOnScroll;
    }

    public function setFixedOnScroll(bool $fixedOnScroll): static
    {
        $this->fixedOnScroll = $fixedOnScroll;

        return $this;
    }

    public function isAlwaysFixed(): ?bool
    {
        return $this->alwaysFixed;
    }

    public function setAlwaysFixed(bool $alwaysFixed): static
    {
        $this->alwaysFixed = $alwaysFixed;

        return $this;
    }

    public function isDropdownHover(): ?bool
    {
        return $this->dropdownHover;
    }

    public function setDropdownHover(bool $dropdownHover): static
    {
        $this->dropdownHover = $dropdownHover;

        return $this;
    }

    public function isVertical(): ?bool
    {
        return $this->vertical;
    }

    public function setVertical(bool $vertical): static
    {
        $this->vertical = $vertical;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getExpand(): ?string
    {
        return $this->expand;
    }

    public function setExpand(string $expand): static
    {
        $this->expand = $expand;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getAlignment(): ?string
    {
        return $this->alignment;
    }

    public function setAlignment(string $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function getMaxLevel(): ?int
    {
        return $this->maxLevel;
    }

    public function setMaxLevel(?int $maxLevel): static
    {
        $this->maxLevel = $maxLevel;

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
     * @return Collection<int, Link>
     */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    public function addLink(Link $link): static
    {
        if (!$this->links->contains($link)) {
            $this->links->add($link);
            $link->setMenu($this);
        }

        return $this;
    }

    public function removeLink(Link $link): static
    {
        if ($this->links->removeElement($link)) {
            // set the owning side to null (unless already changed)
            if ($link->getMenu() === $this) {
                $link->setMenu(null);
            }
        }

        return $this;
    }
}
