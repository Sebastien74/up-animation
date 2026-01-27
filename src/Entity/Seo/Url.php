<?php

declare(strict_types=1);

namespace App\Entity\Seo;

use App\Entity\BaseInterface;
use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Repository\Seo\UrlRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Url.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'seo_url')]
#[ORM\Index(name: 'idx_seo_url_code', columns: ['code'])]
#[ORM\Index(name: 'idx_seo_url_locale', columns: ['locale'])]
#[ORM\Index(name: 'idx_seo_url_online', columns: ['online'])]
#[ORM\Entity(repositoryClass: UrlRepository::class)]
class Url extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'url',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::STRING, length: 1500, nullable: true)]
    private ?string $oldUrl = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $online = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asIndex = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hideInSitemap = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $archived = false;

    #[ORM\OneToOne(targetEntity: Seo::class, inversedBy: 'url', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Seo $seo = null;

    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Page $indexPage = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getOldUrl(): ?string
    {
        return $this->oldUrl;
    }

    public function setOldUrl(?string $oldUrl): static
    {
        $this->oldUrl = $oldUrl;

        return $this;
    }

    public function isOnline(): ?bool
    {
        return $this->online;
    }

    public function setOnline(bool $online): static
    {
        $this->online = $online;

        return $this;
    }

    public function isAsIndex(): ?bool
    {
        return $this->asIndex;
    }

    public function setAsIndex(bool $asIndex): static
    {
        $this->asIndex = $asIndex;

        return $this;
    }

    public function isHideInSitemap(): ?bool
    {
        return $this->hideInSitemap;
    }

    public function setHideInSitemap(bool $hideInSitemap): static
    {
        $this->hideInSitemap = $hideInSitemap;

        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }

    public function getSeo(): ?Seo
    {
        return $this->seo;
    }

    public function setSeo(?Seo $seo): static
    {
        $this->seo = $seo;

        return $this;
    }

    public function getIndexPage(): ?Page
    {
        return $this->indexPage;
    }

    public function setIndexPage(?Page $indexPage): static
    {
        $this->indexPage = $indexPage;

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
