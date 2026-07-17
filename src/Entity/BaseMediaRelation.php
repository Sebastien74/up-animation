<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Media\CropSizes;
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

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $shape = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $popup = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $main = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $header = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $radius = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $rotation = false;

    #[ORM\Embedded(class: CropSizes::class, columnPrefix: false)]
    protected CropSizes $cropSizes;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $position = 1;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $downloadable = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $init = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $active = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $cacheDate = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $pictogram = null;

    #[ORM\OneToOne(targetEntity: MediaRelationIntl::class, cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'intl_id', referencedColumnName: 'id', onDelete: 'cascade')]
    protected ?MediaRelationIntl $intl = null;

    #[ORM\ManyToOne(targetEntity: Media::class, cascade: ['persist'], fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Media $media = null;

    public function __construct()
    {
        $this->cropSizes = new CropSizes();
    }

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

    public function getShape(): ?string
    {
        return $this->shape;
    }

    public function setShape(?string $shape): static
    {
        $this->shape = $shape;

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

    public function isRotation(): ?bool
    {
        return $this->rotation;
    }

    public function setRotation(bool $rotation): static
    {
        $this->rotation = $rotation;

        return $this;
    }

    public function getCropSizes(): CropSizes
    {
        return $this->cropSizes;
    }

    public function setCropSizes(CropSizes $cropSizes): void
    {
        $this->cropSizes = $cropSizes;
    }

    // Tailles par écran : accès délégué à l'embeddable CropSizes.
    // Les noms historiques (desktop/tablet/mobile) sont conservés pour la
    // compatibilité ; l'écran "laptop" (Ordinateur portable) est nouveau.

    public function getMaxWidth(): ?int
    {
        return $this->cropSizes->getCropWidthDesktop();
    }

    public function setMaxWidth(?int $maxWidth): void
    {
        $this->cropSizes->setCropWidthDesktop($maxWidth);
    }

    public function getMaxHeight(): ?int
    {
        return $this->cropSizes->getCropHeightDesktop();
    }

    public function setMaxHeight(?int $maxHeight): void
    {
        $this->cropSizes->setCropHeightDesktop($maxHeight);
    }

    public function getLaptopMaxWidth(): ?int
    {
        return $this->cropSizes->getCropWidthLaptop();
    }

    public function setLaptopMaxWidth(?int $laptopMaxWidth): void
    {
        $this->cropSizes->setCropWidthLaptop($laptopMaxWidth);
    }

    public function getLaptopMaxHeight(): ?int
    {
        return $this->cropSizes->getCropHeightLaptop();
    }

    public function setLaptopMaxHeight(?int $laptopMaxHeight): void
    {
        $this->cropSizes->setCropHeightLaptop($laptopMaxHeight);
    }

    public function getTabletMaxWidth(): ?int
    {
        return $this->cropSizes->getCropWidthTablet();
    }

    public function setTabletMaxWidth(?int $tabletMaxWidth): void
    {
        $this->cropSizes->setCropWidthTablet($tabletMaxWidth);
    }

    public function getTabletMaxHeight(): ?int
    {
        return $this->cropSizes->getCropHeightTablet();
    }

    public function setTabletMaxHeight(?int $tabletMaxHeight): void
    {
        $this->cropSizes->setCropHeightTablet($tabletMaxHeight);
    }

    public function getMobileMaxWidth(): ?int
    {
        return $this->cropSizes->getCropWidthMobile();
    }

    public function setMobileMaxWidth(?int $mobileMaxWidth): void
    {
        $this->cropSizes->setCropWidthMobile($mobileMaxWidth);
    }

    public function getMobileMaxHeight(): ?int
    {
        return $this->cropSizes->getCropHeightMobile();
    }

    public function setMobileMaxHeight(?int $mobileMaxHeight): void
    {
        $this->cropSizes->setCropHeightMobile($mobileMaxHeight);
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

    public function setCacheDate(?\DateTimeImmutable $cacheDate): static
    {
        $this->cacheDate = $cacheDate;

        return $this;
    }

    public function getCacheDate(): ?\DateTimeImmutable
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
