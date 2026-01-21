<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use App\Repository\Layout\PageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Page.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_page')]
#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'urls',
        joinColumns: [new ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'url_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'layout_page_urls'),
        fetch: 'EXTRA_LAZY'
    ),
])]
class Page extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'page',
        'table_name' => 'layout_page',
        'search' => true,
        'resize' => true,
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asIndex = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $infill = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $secure = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $level = 1;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publicationStart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publicationEnd = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $template = 'cms.html.twig';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $backgroundColor = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $pictogram = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $deletable = true;

    #[ORM\OneToOne(targetEntity: Layout::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'layout_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Layout $layout = null;

    #[ORM\OneToMany(targetEntity: Page::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $pages;

    #[ORM\OneToMany(targetEntity: PageMediaRelation::class, mappedBy: 'page', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\OneToMany(targetEntity: PageIntl::class, mappedBy: 'page', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(name: 'website_id', referencedColumnName: 'id', nullable: false)]
    private ?Website $website = null;

    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'pages')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ?Page $parent = null;

    #[ORM\ManyToMany(targetEntity: Url::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $urls;

    /**
     * Page constructor.
     */
    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->intls = new ArrayCollection();
        $this->urls = new ArrayCollection();
    }

    public function isAsIndex(): ?bool
    {
        return $this->asIndex;
    }

    public function setAsIndex(bool $asIndex): static
    {
        $this->asIndex = $asIndex;

        return $this;
    }

    public function isInfill(): ?bool
    {
        return $this->infill;
    }

    public function setInfill(bool $infill): static
    {
        $this->infill = $infill;

        return $this;
    }

    public function isSecure(): ?bool
    {
        return $this->secure;
    }

    public function setSecure(bool $secure): static
    {
        $this->secure = $secure;

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

    public function getPublicationStart(): ?\DateTimeInterface
    {
        return $this->publicationStart;
    }

    public function setPublicationStart(?\DateTimeInterface $publicationStart): static
    {
        $this->publicationStart = $publicationStart;

        return $this;
    }

    public function getPublicationEnd(): ?\DateTimeInterface
    {
        return $this->publicationEnd;
    }

    public function setPublicationEnd(?\DateTimeInterface $publicationEnd): static
    {
        $this->publicationEnd = $publicationEnd;

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

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor): static
    {
        $this->backgroundColor = $backgroundColor;

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

    public function isDeletable(): ?bool
    {
        return $this->deletable;
    }

    public function setDeletable(bool $deletable): static
    {
        $this->deletable = $deletable;

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
     * @return Collection<int, Page>
     */
    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(Page $page): static
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
            $page->setParent($this);
        }

        return $this;
    }

    public function removePage(Page $page): static
    {
        if ($this->pages->removeElement($page)) {
            // set the owning side to null (unless already changed)
            if ($page->getParent() === $this) {
                $page->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PageMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(PageMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setPage($this);
        }

        return $this;
    }

    public function removeMediaRelation(PageMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getPage() === $this) {
                $mediaRelation->setPage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PageIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(PageIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setPage($this);
        }

        return $this;
    }

    public function removeIntl(PageIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getPage() === $this) {
                $intl->setPage(null);
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, Url>
     */
    public function getUrls(): Collection
    {
        return $this->urls;
    }

    public function addUrl(Url $url): static
    {
        if (!$this->urls->contains($url)) {
            $this->urls->add($url);
        }

        return $this;
    }

    public function removeUrl(Url $url): static
    {
        $this->urls->removeElement($url);

        return $this;
    }
}
