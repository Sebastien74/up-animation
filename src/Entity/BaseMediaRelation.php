<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Media\Media;
use App\Entity\Media\MediaRelationIntl;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * BaseMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class BaseMediaRelation extends BaseInterface
{
    protected static array $interface = [
        'name' => 'mediarelation',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    protected ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $body = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $categorySlug = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $popup = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $main = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $header = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $radius = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $maxWidth = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $maxHeight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $tabletMaxWidth = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $tabletMaxHeight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $mobileMaxWidth = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $mobileMaxHeight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $position = 1;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $downloadable = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $init = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $active = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $cacheDate = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $pictogram = null;

    #[ORM\OneToOne(targetEntity: MediaRelationIntl::class, cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'intl_id', referencedColumnName: 'id', onDelete: 'cascade')]
    protected ?MediaRelationIntl $intl = null;

    #[ORM\ManyToOne(targetEntity: Media::class, cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Media $media = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getCategorySlug(): ?string
    {
        return $this->categorySlug;
    }

    public function setCategorySlug(?string $categorySlug): static
    {
        $this->categorySlug = $categorySlug;

        return $this;
    }

    public function isPopup(): ?bool
    {
        return $this->popup;
    }

    public function setPopup(bool $popup): static
    {
        $this->popup = $popup;

        return $this;
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

    public function isHeader(): ?bool
    {
        return $this->header;
    }

    public function setHeader(bool $header): static
    {
        $this->header = $header;

        return $this;
    }

    public function isRadius(): ?bool
    {
        return $this->radius;
    }

    public function setRadius(bool $radius): static
    {
        $this->radius = $radius;

        return $this;
    }

    public function getMaxWidth(): ?int
    {
        return $this->maxWidth;
    }

    public function setMaxWidth(?int $maxWidth): void
    {
        $this->maxWidth = $maxWidth;
    }

    public function getMaxHeight(): ?int
    {
        return $this->maxHeight;
    }

    public function setMaxHeight(?int $maxHeight): void
    {
        $this->maxHeight = $maxHeight;
    }

    public function getTabletMaxWidth(): ?int
    {
        return $this->tabletMaxWidth;
    }

    public function setTabletMaxWidth(?int $tabletMaxWidth): void
    {
        $this->tabletMaxWidth = $tabletMaxWidth;
    }

    public function getTabletMaxHeight(): ?int
    {
        return $this->tabletMaxHeight;
    }

    public function setTabletMaxHeight(?int $tabletMaxHeight): void
    {
        $this->tabletMaxHeight = $tabletMaxHeight;
    }

    public function getMobileMaxWidth(): ?int
    {
        return $this->mobileMaxWidth;
    }

    public function setMobileMaxWidth(?int $mobileMaxWidth): void
    {
        $this->mobileMaxWidth = $mobileMaxWidth;
    }

    public function getMobileMaxHeight(): ?int
    {
        return $this->mobileMaxHeight;
    }

    public function setMobileMaxHeight(?int $mobileMaxHeight): void
    {
        $this->mobileMaxHeight = $mobileMaxHeight;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isDownloadable(): ?bool
    {
        return $this->downloadable;
    }

    public function setDownloadable(bool $downloadable): static
    {
        $this->downloadable = $downloadable;

        return $this;
    }

    public function isInit(): ?bool
    {
        return $this->init;
    }

    public function setInit(bool $init): static
    {
        $this->init = $init;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function setCacheDate(?\DateTimeInterface $cacheDate): static
    {
        $this->cacheDate = $cacheDate;

        return $this;
    }

    public function getCacheDate(): ?\DateTimeInterface
    {
        return $this->cacheDate;
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

    public function getIntl(): ?MediaRelationIntl
    {
        return $this->intl;
    }

    public function setIntl(?MediaRelationIntl $intl): static
    {
        $this->intl = $intl;

        return $this;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): static
    {
        $this->media = $media;

        return $this;
    }
}
