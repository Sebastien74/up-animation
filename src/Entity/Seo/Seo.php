<?php

declare(strict_types=1);

namespace App\Entity\Seo;

use App\Entity\BaseInterface;
use App\Entity\Media\MediaRelation;
use App\Repository\Seo\SeoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Seo.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'seo')]
#[ORM\Entity(repositoryClass: SeoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Seo extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'seo',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $noAfterDash = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaTitleSecond = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $breadcrumbTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $keywords = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $authorType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metadata = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $footerDescription = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaCanonical = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaOgTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaOgDescription = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaOgTwitterCard = 'summary';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaOgTwitterHandle = null;

    #[ORM\OneToOne(targetEntity: Url::class, mappedBy: 'seo', cascade: ['persist', 'remove'])]
    private ?Url $url = null;

    #[ORM\OneToOne(targetEntity: MediaRelation::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'media_relation_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?MediaRelation $mediaRelation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isNoAfterDash(): ?bool
    {
        return $this->noAfterDash;
    }

    public function setNoAfterDash(bool $noAfterDash): static
    {
        $this->noAfterDash = $noAfterDash;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaTitleSecond(): ?string
    {
        return $this->metaTitleSecond;
    }

    public function setMetaTitleSecond(?string $metaTitleSecond): static
    {
        $this->metaTitleSecond = $metaTitleSecond;

        return $this;
    }

    public function getBreadcrumbTitle(): ?string
    {
        return $this->breadcrumbTitle;
    }

    public function setBreadcrumbTitle(?string $breadcrumbTitle): static
    {
        $this->breadcrumbTitle = $breadcrumbTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): static
    {
        $this->keywords = $keywords;

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

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function setMetadata(?string $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getFooterDescription(): ?string
    {
        return $this->footerDescription;
    }

    public function setFooterDescription(?string $footerDescription): static
    {
        $this->footerDescription = $footerDescription;

        return $this;
    }

    public function getMetaCanonical(): ?string
    {
        return $this->metaCanonical;
    }

    public function setMetaCanonical(?string $metaCanonical): static
    {
        $this->metaCanonical = $metaCanonical;

        return $this;
    }

    public function getMetaOgTitle(): ?string
    {
        return $this->metaOgTitle;
    }

    public function setMetaOgTitle(?string $metaOgTitle): static
    {
        $this->metaOgTitle = $metaOgTitle;

        return $this;
    }

    public function getMetaOgDescription(): ?string
    {
        return $this->metaOgDescription;
    }

    public function setMetaOgDescription(?string $metaOgDescription): static
    {
        $this->metaOgDescription = $metaOgDescription;

        return $this;
    }

    public function getMetaOgTwitterCard(): ?string
    {
        return $this->metaOgTwitterCard;
    }

    public function setMetaOgTwitterCard(?string $metaOgTwitterCard): static
    {
        $this->metaOgTwitterCard = $metaOgTwitterCard;

        return $this;
    }

    public function getMetaOgTwitterHandle(): ?string
    {
        return $this->metaOgTwitterHandle;
    }

    public function setMetaOgTwitterHandle(?string $metaOgTwitterHandle): static
    {
        $this->metaOgTwitterHandle = $metaOgTwitterHandle;

        return $this;
    }

    public function getUrl(): ?Url
    {
        return $this->url;
    }

    public function setUrl(?Url $url): static
    {
        // unset the owning side of the relation if necessary
        if (null === $url && null !== $this->url) {
            $this->url->setSeo(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $url && $url->getSeo() !== $this) {
            $url->setSeo($this);
        }

        $this->url = $url;

        return $this;
    }

    public function getMediaRelation(): ?MediaRelation
    {
        return $this->mediaRelation;
    }

    public function setMediaRelation(?MediaRelation $mediaRelation): static
    {
        $this->mediaRelation = $mediaRelation;

        return $this;
    }
}
