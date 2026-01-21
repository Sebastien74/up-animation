<?php

declare(strict_types=1);

namespace App\Entity\Module\Menu;

use App\Entity\BaseEntity;
use App\Repository\Module\Menu\LinkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Link.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_menu_link')]
#[ORM\Entity(repositoryClass: LinkRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Link extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'menu';
    protected static array $interface = [
        'name' => 'link',
    ];

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $level = 1;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $backgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $btnColor = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $pictogram = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $icon = null;

    #[ORM\OneToOne(targetEntity: LinkIntl::class, inversedBy: 'link', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'intl_id', referencedColumnName: 'id', onDelete: 'cascade')]
    private ?LinkIntl $intl = null;

    #[ORM\OneToOne(targetEntity: LinkMediaRelation::class, inversedBy: 'link', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'media_relation_id', referencedColumnName: 'id', onDelete: 'cascade')]
    private ?LinkMediaRelation $mediaRelation = null;

    #[ORM\ManyToOne(targetEntity: Menu::class, inversedBy: 'links')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Menu $menu = null;

    #[ORM\OneToMany(targetEntity: Link::class, mappedBy: 'parent')]
    private ArrayCollection|PersistentCollection $links;

    #[ORM\ManyToOne(targetEntity: Link::class, inversedBy: 'links')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'cascade')]
    private ?Link $parent = null;

    /**
     * Link constructor.
     */

    public function __construct()
    {
        $this->links = new ArrayCollection();
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): static
    {
        $this->level = $level;

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

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor): static
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function getBtnColor(): ?string
    {
        return $this->btnColor;
    }

    public function setBtnColor(?string $btnColor): static
    {
        $this->btnColor = $btnColor;

        return $this;
    }

    public function getPictogram(): ?string
    {
        return $this->pictogram;
    }

    public function setPictogram(?string $pictogram): static
    {
        $this->pictogram = $pictogram;

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

    public function getIntl(): ?LinkIntl
    {
        return $this->intl;
    }

    public function setIntl(?LinkIntl $intl): static
    {
        $this->intl = $intl;

        return $this;
    }

    public function getMediaRelation(): ?LinkMediaRelation
    {
        return $this->mediaRelation;
    }

    public function setMediaRelation(?LinkMediaRelation $mediaRelation): static
    {
        $this->mediaRelation = $mediaRelation;

        return $this;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;

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
            $link->setParent($this);
        }

        return $this;
    }

    public function removeLink(Link $link): static
    {
        if ($this->links->removeElement($link)) {
            // set the owning side to null (unless already changed)
            if ($link->getParent() === $this) {
                $link->setParent(null);
            }
        }

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }
}
