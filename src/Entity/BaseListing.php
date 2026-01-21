<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BaseListing.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class BaseListing extends BaseEntity
{
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hideDate = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayFilters = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $filtersInline = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayCategory = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $displayThumbnail = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $largeFirst = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $scrollInfinite = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $showMoreBtn = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $groupByCategory = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $formatDate = 'dd MMM Y';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private ?int $itemsPerPage = 12;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $orderBy = 'publicationStart-desc';

    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Page $page = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    public function isHideDate(): ?bool
    {
        return $this->hideDate;
    }

    public function setHideDate(bool $hideDate): static
    {
        $this->hideDate = $hideDate;

        return $this;
    }

    public function isDisplayFilters(): ?bool
    {
        return $this->displayFilters;
    }

    public function setDisplayFilters(bool $displayFilters): static
    {
        $this->displayFilters = $displayFilters;

        return $this;
    }

    public function isFiltersInline(): ?bool
    {
        return $this->filtersInline;
    }

    public function setFiltersInline(bool $filtersInline): static
    {
        $this->filtersInline = $filtersInline;

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

    public function isDisplayThumbnail(): ?bool
    {
        return $this->displayThumbnail;
    }

    public function setDisplayThumbnail(bool $displayThumbnail): static
    {
        $this->displayThumbnail = $displayThumbnail;

        return $this;
    }

    public function isLargeFirst(): ?bool
    {
        return $this->largeFirst;
    }

    public function setLargeFirst(bool $largeFirst): static
    {
        $this->largeFirst = $largeFirst;

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

    public function isShowMoreBtn(): ?bool
    {
        return $this->showMoreBtn;
    }

    public function setShowMoreBtn(bool $showMoreBtn): static
    {
        $this->showMoreBtn = $showMoreBtn;

        return $this;
    }

    public function isGroupByCategory(): ?bool
    {
        return $this->groupByCategory;
    }

    public function setGroupByCategory(bool $groupByCategory): static
    {
        $this->groupByCategory = $groupByCategory;

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

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;

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
