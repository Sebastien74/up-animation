<?php

declare(strict_types=1);

namespace App\Entity\Seo;

use App\Entity\BaseInterface;
use App\Entity\Core\Website;
use App\Repository\Seo\ModelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Model.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'seo_model')]
#[ORM\Index(columns: ['locale'], name: 'idx_seo_model_locale')]
#[ORM\Index(columns: ['class_name'], name: 'idx_seo_model_class_name')]
#[ORM\Entity(repositoryClass: ModelRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Model extends BaseInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $adminName = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $noAfterDash = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaTitleSecond = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $keywords = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $footerDescription = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaOgTitle = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $metaOgDescription = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $className = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $childClassName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdminName(): ?string
    {
        return $this->adminName;
    }

    public function setAdminName(?string $adminName): static
    {
        $this->adminName = $adminName;

        return $this;
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

    public function getFooterDescription(): ?string
    {
        return $this->footerDescription;
    }

    public function setFooterDescription(?string $footerDescription): static
    {
        $this->footerDescription = $footerDescription;

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

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(string $className): static
    {
        $this->className = $className;

        return $this;
    }

    public function getChildClassName(): ?string
    {
        return $this->childClassName;
    }

    public function setChildClassName(?string $childClassName): static
    {
        $this->childClassName = $childClassName;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

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
