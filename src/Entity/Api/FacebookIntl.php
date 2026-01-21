<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\FacebookIntlRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * FacebookIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_facebook_intl')]
#[ORM\Entity(repositoryClass: FacebookIntlRepository::class)]
class FacebookIntl
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $domainVerification = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $pixel = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $phoneTrack = false;

    #[ORM\ManyToOne(targetEntity: Facebook::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Facebook $facebook = null;

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

    public function getDomainVerification(): ?string
    {
        return $this->domainVerification;
    }

    public function setDomainVerification(?string $domainVerification): static
    {
        $this->domainVerification = $domainVerification;

        return $this;
    }

    public function getPixel(): ?string
    {
        return $this->pixel;
    }

    public function setPixel(?string $pixel): static
    {
        $this->pixel = $pixel;

        return $this;
    }

    public function isPhoneTrack(): ?bool
    {
        return $this->phoneTrack;
    }

    public function setPhoneTrack(bool $phoneTrack): static
    {
        $this->phoneTrack = $phoneTrack;

        return $this;
    }

    public function getFacebook(): ?Facebook
    {
        return $this->facebook;
    }

    public function setFacebook(?Facebook $facebook): static
    {
        $this->facebook = $facebook;

        return $this;
    }
}
