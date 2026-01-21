<?php

declare(strict_types=1);

namespace App\Entity\Module\Search;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Layout\Page;
use App\Entity\Module\Newscast\Newscast;
use App\Repository\Module\Search\SearchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Search.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_search')]
#[ORM\Entity(repositoryClass: SearchRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Search extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'search',
    ];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $entities = [Newscast::class, Block::class];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $filterGroup = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $modal = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $registerSearch = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $scrollInfinite = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $itemsPerPage = 9;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $counter = true;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $mode = 'boolean';

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $orderBy = 'date-desc';

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $searchType = 'sentence';

    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(name: 'results_page_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Page $resultsPage = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\OneToMany(mappedBy: 'search', targetEntity: SearchValue::class, cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $values;

    /**
     * Search constructor.
     */
    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    public function getEntities(): ?array
    {
        return $this->entities;
    }

    public function setEntities(?array $entities): static
    {
        $this->entities = $entities;

        return $this;
    }

    public function isFilterGroup(): ?bool
    {
        return $this->filterGroup;
    }

    public function setFilterGroup(bool $filterGroup): static
    {
        $this->filterGroup = $filterGroup;

        return $this;
    }

    public function isModal(): ?bool
    {
        return $this->modal;
    }

    public function setModal(bool $modal): static
    {
        $this->modal = $modal;

        return $this;
    }

    public function isRegisterSearch(): ?bool
    {
        return $this->registerSearch;
    }

    public function setRegisterSearch(bool $registerSearch): static
    {
        $this->registerSearch = $registerSearch;

        return $this;
    }

    public function isScrollInfinite(): ?bool
    {
        return $this->scrollInfinite;
    }

    public function setScrollInfinite(bool $scrollInfinite): static
    {
        $this->scrollInfinite = $scrollInfinite;

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

    public function isCounter(): ?bool
    {
        return $this->counter;
    }

    public function setCounter(bool $counter): static
    {
        $this->counter = $counter;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;

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

    public function getSearchType(): ?string
    {
        return $this->searchType;
    }

    public function setSearchType(string $searchType): static
    {
        $this->searchType = $searchType;

        return $this;
    }

    public function getResultsPage(): ?Page
    {
        return $this->resultsPage;
    }

    public function setResultsPage(?Page $resultsPage): static
    {
        $this->resultsPage = $resultsPage;

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
     * @return Collection<int, SearchValue>
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    public function addValue(SearchValue $value): static
    {
        if (!$this->values->contains($value)) {
            $this->values->add($value);
            $value->setSearch($this);
        }

        return $this;
    }

    public function removeValue(SearchValue $value): static
    {
        if ($this->values->removeElement($value)) {
            // set the owning side to null (unless already changed)
            if ($value->getSearch() === $this) {
                $value->setSearch(null);
            }
        }

        return $this;
    }
}
