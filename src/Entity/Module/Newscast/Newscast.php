<?php

declare(strict_types=1);

namespace App\Entity\Module\Newscast;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Entity\Layout\Layout;
use App\Entity\Seo\Url;
use App\Repository\Module\Newscast\NewscastRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Newscast.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newscast')]
#[ORM\Entity(repositoryClass: NewscastRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'urls',
        joinColumns: [new ORM\JoinColumn(name: 'newscast_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'url_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'module_newscast_urls')
    ),
])]
class Newscast extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'newscast',
        'card' => true,
        'search' => true,
        'resize' => true,
        'indexPage' => 'category',
        'listingClass' => Listing::class,
        'seo' => [
            'intl.title',
            'intl.introduction',
            'intl.body',
        ],
    ];
    protected static array $labels = [
        'intl.title' => 'Titre',
        'intl.introduction' => 'Introduction',
        'intl.body' => 'Description',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $promote = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publicationStart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publicationEnd = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publicationDate = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $customLayout = false;

    #[ORM\OneToOne(targetEntity: Layout::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'layout_id', referencedColumnName: 'id')]
    private ?Layout $layout = null;

    #[ORM\OneToMany(mappedBy: 'newscast', targetEntity: Comment::class, orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $comments;

    #[ORM\OneToMany(mappedBy: 'newscast', targetEntity: NewscastIntl::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\OneToMany(mappedBy: 'newscast', targetEntity: NewscastMediaRelation::class, cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\ManyToOne(targetEntity: Category::class, cascade: ['persist'], fetch: 'EAGER', inversedBy: 'newscasts')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\ManyToMany(targetEntity: Url::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $urls;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'newscasts')]
    #[ORM\JoinTable(name: 'module_newscasts_tags')]
    private ArrayCollection|PersistentCollection $tags;

    /**
     * Newscast constructor.
     */
    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->intls = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    /**
     * @throws Exception
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (empty($this->publicationStart)) {
            $this->publicationStart = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        }
        parent::prePersist();
    }

    public function isPromote(): ?bool
    {
        return $this->promote;
    }

    public function setPromote(bool $promote): static
    {
        $this->promote = $promote;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

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

    public function getPublicationDate(): ?\DateTimeInterface
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?\DateTimeInterface $publicationDate): static
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    public function isCustomLayout(): ?bool
    {
        return $this->customLayout;
    }

    public function setCustomLayout(bool $customLayout): static
    {
        $this->customLayout = $customLayout;

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
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setNewscast($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getNewscast() === $this) {
                $comment->setNewscast(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, NewscastIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(NewscastIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setNewscast($this);
        }

        return $this;
    }

    public function removeIntl(NewscastIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getNewscast() === $this) {
                $intl->setNewscast(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, NewscastMediaRelation>
     */
    public function getMediaRelations(): Collection
    {
        return $this->mediaRelations;
    }

    public function addMediaRelation(NewscastMediaRelation $mediaRelation): static
    {
        if (!$this->mediaRelations->contains($mediaRelation)) {
            $this->mediaRelations->add($mediaRelation);
            $mediaRelation->setNewscast($this);
        }

        return $this;
    }

    public function removeMediaRelation(NewscastMediaRelation $mediaRelation): static
    {
        if ($this->mediaRelations->removeElement($mediaRelation)) {
            // set the owning side to null (unless already changed)
            if ($mediaRelation->getNewscast() === $this) {
                $mediaRelation->setNewscast(null);
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

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
