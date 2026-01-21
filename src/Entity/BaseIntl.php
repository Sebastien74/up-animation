<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * BaseIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Index(columns: ['title'], flags: ['fulltext'])]
#[ORM\Index(columns: ['introduction'], flags: ['fulltext'])]
#[ORM\Index(columns: ['body'], flags: ['fulltext'])]
#[ORM\Index(columns: ['associatedWords'], flags: ['fulltext'])]
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class BaseIntl extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'intl',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    protected ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $slug = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $titleForce = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $subTitle = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    protected ?string $subTitlePosition = 'bottom';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $introduction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $body = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $targetLink = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $targetLabel = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $targetStyle = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $newTab = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $externalLink = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $active = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $placeholder = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $author = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $authorType = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $help = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $error = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    protected ?string $pictogram = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $video = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $associatedWords = null;

    #[ORM\ManyToOne(targetEntity: Page::class, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    protected ?Page $targetPage = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTitleForce(): ?int
    {
        return $this->titleForce;
    }

    public function setTitleForce(?int $titleForce): static
    {
        $this->titleForce = $titleForce;

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

    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    public function setSubTitle(?string $subTitle): static
    {
        $this->subTitle = $subTitle;

        return $this;
    }

    public function getSubTitlePosition(): ?string
    {
        return $this->subTitlePosition;
    }

    public function setSubTitlePosition(?string $subTitlePosition): static
    {
        $this->subTitlePosition = $subTitlePosition;

        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): static
    {
        $this->introduction = $introduction;

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

    public function getTargetLink(): ?string
    {
        return $this->targetLink;
    }

    public function setTargetLink(?string $targetLink): static
    {
        $this->targetLink = $targetLink;

        return $this;
    }

    public function getTargetLabel(): ?string
    {
        return $this->targetLabel;
    }

    public function setTargetLabel(?string $targetLabel): static
    {
        $this->targetLabel = $targetLabel;

        return $this;
    }

    public function getTargetStyle(): ?string
    {
        return $this->targetStyle;
    }

    public function setTargetStyle(?string $targetStyle): static
    {
        $this->targetStyle = $targetStyle;

        return $this;
    }

    public function isNewTab(): ?bool
    {
        return $this->newTab;
    }

    public function setNewTab(bool $newTab): static
    {
        $this->newTab = $newTab;

        return $this;
    }

    public function isExternalLink(): ?bool
    {
        return $this->externalLink;
    }

    public function setExternalLink(bool $externalLink): static
    {
        $this->externalLink = $externalLink;

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

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function setPlaceholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

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

    public function getAuthorType(): ?string
    {
        return $this->authorType;
    }

    public function setAuthorType(?string $authorType): static
    {
        $this->authorType = $authorType;

        return $this;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setHelp(?string $help): static
    {
        $this->help = $help;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error;

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

    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function setVideo(?string $video): static
    {
        $this->video = $video;

        return $this;
    }

    public function getAssociatedWords(): ?string
    {
        return $this->associatedWords;
    }

    public function setAssociatedWords(?string $associatedWords): static
    {
        $this->associatedWords = $associatedWords;

        return $this;
    }

    public function getTargetPage(): ?Page
    {
        return $this->targetPage;
    }

    public function setTargetPage(?Page $targetPage): static
    {
        $this->targetPage = $targetPage;

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
