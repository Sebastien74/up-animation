<?php

declare(strict_types=1);

namespace App\Entity\Information;

use App\Repository\Information\SocialNetworkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * SocialNetwork.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'information_social_network')]
#[ORM\Entity(repositoryClass: SocialNetworkRepository::class)]
class SocialNetwork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $twitter = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $facebook = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $google = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $youtube = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tiktok = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $instagram = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $linkedin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pinterest = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tripadvisor = null;

    #[ORM\ManyToOne(targetEntity: Information::class, inversedBy: 'socialNetworks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Information $information = null;

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

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(?string $twitter): static
    {
        $this->twitter = $twitter;

        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): static
    {
        $this->facebook = $facebook;

        return $this;
    }

    public function getGoogle(): ?string
    {
        return $this->google;
    }

    public function setGoogle(?string $google): static
    {
        $this->google = $google;

        return $this;
    }

    public function getYoutube(): ?string
    {
        return $this->youtube;
    }

    public function setYoutube(?string $youtube): static
    {
        $this->youtube = $youtube;

        return $this;
    }

    public function getTiktok(): ?string
    {
        return $this->tiktok;
    }

    public function setTiktok(?string $tiktok): static
    {
        $this->tiktok = $tiktok;

        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): static
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function setLinkedin(?string $linkedin): static
    {
        $this->linkedin = $linkedin;

        return $this;
    }

    public function getPinterest(): ?string
    {
        return $this->pinterest;
    }

    public function setPinterest(?string $pinterest): static
    {
        $this->pinterest = $pinterest;

        return $this;
    }

    public function getTripadvisor(): ?string
    {
        return $this->tripadvisor;
    }

    public function setTripadvisor(?string $tripadvisor): static
    {
        $this->tripadvisor = $tripadvisor;

        return $this;
    }

    public function getInformation(): ?Information
    {
        return $this->information;
    }

    public function setInformation(?Information $information): static
    {
        $this->information = $information;

        return $this;
    }
}
