<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Core\Website;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BaseTeaser.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class BaseTeaser extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $promote = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $promoteFirst = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $progress = true;

    #[ORM\Column(type: Types::STRING, length: 100)]
    protected ?string $template = 'slider';

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $matchCategories = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $formatDate = 'dd MMM Y';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected array $fields = ['image', 'title', 'card-link', 'index-link'];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    protected ?int $nbrItems = 15;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    protected ?int $itemsPerSlide = 1;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected string $orderBy = 'publicationStart-desc';

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    public function isPromote(): ?bool
    {
        return $this->promote;
    }

    public function setPromote(bool $promote): static
    {
        $this->promote = $promote;

        return $this;
    }

    public function isPromoteFirst(): ?bool
    {
        return $this->promoteFirst;
    }

    public function setPromoteFirst(bool $promoteFirst): static
    {
        $this->promoteFirst = $promoteFirst;

        return $this;
    }

    public function isProgress(): ?bool
    {
        return $this->progress;
    }

    public function setProgress(bool $progress): static
    {
        $this->progress = $progress;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(?string $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function isMatchCategories(): ?bool
    {
        return $this->matchCategories;
    }

    public function setMatchCategories(bool $matchCategories): static
    {
        $this->matchCategories = $matchCategories;

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

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function getNbrItems(): ?int
    {
        return $this->nbrItems;
    }

    public function setNbrItems(?int $nbrItems): static
    {
        $this->nbrItems = $nbrItems;

        return $this;
    }

    public function getItemsPerSlide(): ?int
    {
        return $this->itemsPerSlide;
    }

    public function setItemsPerSlide(?int $itemsPerSlide): static
    {
        $this->itemsPerSlide = $itemsPerSlide;

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
