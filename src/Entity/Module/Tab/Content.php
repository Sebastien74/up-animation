<?php

declare(strict_types=1);

namespace App\Entity\Module\Tab;

use App\Entity\BaseEntity;
use App\Repository\Module\Tab\ContentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Content.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_tab_content')]
#[ORM\Entity(repositoryClass: ContentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Content extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'tab';
    protected static array $interface = [
        'name' => 'tabcontent',
        'search' => true,
    ];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $level = 1;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $pictogram = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $largeBullets = false;

    #[ORM\OneToMany(targetEntity: Content::class, mappedBy: 'parent')]
    private ArrayCollection|PersistentCollection $contents;

    #[ORM\OneToMany(targetEntity: ContentMediaRelation::class, mappedBy: 'content', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: ContentIntl::class, mappedBy: 'content', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Content::class, inversedBy: 'contents')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'cascade')]
    private ?Content $parent = null;

    #[ORM\ManyToOne(targetEntity: Tab::class, inversedBy: 'contents')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Tab $tab = null;

    /**
     * Content constructor.
     */
    public function __construct()
    {
        $this->contents = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
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

    public function getPictogram(): ?string
    {
        return $this->pictogram;
    }

    public function setPictogram(?string $pictogram): static
    {
        $this->pictogram = $pictogram;

        return $this;
    }

    public function isLargeBullets(): ?bool
    {
        return $this->largeBullets;
    }

    public function setLargeBullets(bool $largeBullets): static
    {
        $this->largeBullets = $largeBullets;

        return $this;
    }

    /**
     * @return Collection<int, Content>
     */
    public function getContents(): Collection
    {
        return $this->contents;
    }

    public function addContent(Content $content): static
    {
        if (!$this->contents->contains($content)) {
            $this->contents->add($content);
            $content->setParent($this);
        }

        return $this;
    }

    public function removeContent(Content $content): static
    {
        if ($this->contents->removeElement($content)) {
            // set the owning side to null (unless already changed)
            if ($content->getParent() === $this) {
                $content->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContentMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(ContentMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setContent($this);
        }

        return $this;
    }

    public function removeMediaRelation(ContentMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getContent() === $this) {
                $mediaRelation->setContent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContentIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(ContentIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setContent($this);
        }

        return $this;
    }

    public function removeIntl(ContentIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getContent() === $this) {
                $intl->setContent(null);
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

    public function getTab(): ?Tab
    {
        return $this->tab;
    }

    public function setTab(?Tab $tab): static
    {
        $this->tab = $tab;

        return $this;
    }
}
