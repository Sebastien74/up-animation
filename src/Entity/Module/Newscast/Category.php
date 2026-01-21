<?php

declare(strict_types=1);

namespace App\Entity\Module\Newscast;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Entity\Layout\Layout;
use App\Repository\Module\Newscast\CategoryRepository;
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
#[ORM\Table(name: 'module_newscast_category')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Category extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'newscastcategory',
        'resize' => true,
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asDefault = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $asEvents = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $mainMediaInHeader = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hideDate = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayCategory = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $useDefaultTemplate = true;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $formatDate = 'dd MMM';

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $itemsPerPage = 12;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $orderBy = 'publicationStart-desc';

    #[ORM\OneToOne(targetEntity: Layout::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'layout_id', referencedColumnName: 'id')]
    private ?Layout $layout = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Newscast::class, cascade: ['persist'])]
    #[ORM\OrderBy(['publicationStart' => 'DESC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $newscasts;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CategoryIntl::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CategoryMediaRelation::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC', 'locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $mediaRelations;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $categoryTemplate = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->newscasts = new ArrayCollection();
        $this->intls = new ArrayCollection();
        $this->mediaRelations = new ArrayCollection();
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

    public function isAsEvents(): ?bool
    {
        return $this->asEvents;
    }

    public function setAsEvents(bool $asEvents): static
    {
        $this->asEvents = $asEvents;

        return $this;
    }

    public function isMainMediaInHeader(): ?bool
    {
        return $this->mainMediaInHeader;
    }

    public function setMainMediaInHeader(bool $mainMediaInHeader): static
    {
        $this->mainMediaInHeader = $mainMediaInHeader;

        return $this;
    }

    public function isHideDate(): ?bool
    {
        return $this->hideDate;
    }

    public function setHideDate(bool $hideDate): static
    {
        $this->hideDate = $hideDate;

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

    public function isUseDefaultTemplate(): ?bool
    {
        return $this->useDefaultTemplate;
    }

    public function setUseDefaultTemplate(bool $useDefaultTemplate): static
    {
        $this->useDefaultTemplate = $useDefaultTemplate;

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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

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

    public function getItemsPerPage(): ?int
    {
        return $this->itemsPerPage;
    }

    public function setItemsPerPage(?int $itemsPerPage): static
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    public function setOrderBy(string $orderBy): static
    {
        $this->orderBy = $orderBy;

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
     * @return Collection<int, Newscast>
     */
    public function getNewscasts(): Collection
    {
        return $this->newscasts;
    }

    public function addNewscast(Newscast $newscast): static
    {
        if (!$this->newscasts->contains($newscast)) {
            $this->newscasts->add($newscast);
            $newscast->setCategory($this);
        }

        return $this;
    }

    public function removeNewscast(Newscast $newscast): static
    {
        if ($this->newscasts->removeElement($newscast)) {
            // set the owning side to null (unless already changed)
            if ($newscast->getCategory() === $this) {
                $newscast->setCategory(null);
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

    public function getCategoryTemplate(): ?self
    {
        return $this->categoryTemplate;
    }

    public function setCategoryTemplate(?self $categoryTemplate): static
    {
        $this->categoryTemplate = $categoryTemplate;

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
