<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\CustomIntlRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * CustomIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_custom_intl')]
#[ORM\Entity(repositoryClass: CustomIntlRepository::class)]
class CustomIntl
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $axeptioExternal = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $matomoId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $matomoUrl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $axeptioId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $axeptioCookieVersion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $headScript = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $topBodyScript = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bottomBodyScript = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $headScriptSeo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $topBodyScriptSeo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bottomBodyScriptSeo = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $aiFelixSiteId = null;

    #[ORM\ManyToOne(targetEntity: Custom::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Custom $custom = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function isAxeptioExternal(): ?bool
    {
        return $this->axeptioExternal;
    }

    public function setAxeptioExternal(bool $axeptioExternal): static
    {
        $this->axeptioExternal = $axeptioExternal;

        return $this;
    }

    public function getMatomoId(): ?string
    {
        return $this->matomoId;
    }

    public function setMatomoId(?string $matomoId): static
    {
        $this->matomoId = $matomoId;

        return $this;
    }

    public function getMatomoUrl(): ?string
    {
        return $this->matomoUrl;
    }

    public function setMatomoUrl(?string $matomoUrl): static
    {
        $this->matomoUrl = $matomoUrl;

        return $this;
    }

    public function getAxeptioId(): ?string
    {
        return $this->axeptioId;
    }

    public function setAxeptioId(?string $axeptioId): static
    {
        $this->axeptioId = $axeptioId;

        return $this;
    }

    public function getAxeptioCookieVersion(): ?string
    {
        return $this->axeptioCookieVersion;
    }

    public function setAxeptioCookieVersion(?string $axeptioCookieVersion): static
    {
        $this->axeptioCookieVersion = $axeptioCookieVersion;

        return $this;
    }

    public function getHeadScript(): ?string
    {
        return $this->headScript;
    }

    public function setHeadScript(?string $headScript): static
    {
        $this->headScript = $headScript;

        return $this;
    }

    public function getTopBodyScript(): ?string
    {
        return $this->topBodyScript;
    }

    public function setTopBodyScript(?string $topBodyScript): static
    {
        $this->topBodyScript = $topBodyScript;

        return $this;
    }

    public function getBottomBodyScript(): ?string
    {
        return $this->bottomBodyScript;
    }

    public function setBottomBodyScript(?string $bottomBodyScript): static
    {
        $this->bottomBodyScript = $bottomBodyScript;

        return $this;
    }

    public function getHeadScriptSeo(): ?string
    {
        return $this->headScriptSeo;
    }

    public function setHeadScriptSeo(?string $headScriptSeo): static
    {
        $this->headScriptSeo = $headScriptSeo;

        return $this;
    }

    public function getTopBodyScriptSeo(): ?string
    {
        return $this->topBodyScriptSeo;
    }

    public function setTopBodyScriptSeo(?string $topBodyScriptSeo): static
    {
        $this->topBodyScriptSeo = $topBodyScriptSeo;

        return $this;
    }

    public function getBottomBodyScriptSeo(): ?string
    {
        return $this->bottomBodyScriptSeo;
    }

    public function setBottomBodyScriptSeo(?string $bottomBodyScriptSeo): static
    {
        $this->bottomBodyScriptSeo = $bottomBodyScriptSeo;

        return $this;
    }

    public function getAiFelixSiteId(): ?string
    {
        return $this->aiFelixSiteId;
    }

    public function setAiFelixSiteId(?string $AiFelixSiteId): void
    {
        $this->aiFelixSiteId = $AiFelixSiteId;
    }

    public function getCustom(): ?Custom
    {
        return $this->custom;
    }

    public function setCustom(?Custom $custom): static
    {
        $this->custom = $custom;

        return $this;
    }
}
