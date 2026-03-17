<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\TikTokRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * TikTok.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_tiktok')]
#[ORM\Entity(repositoryClass: TikTokRepository::class)]
class TikTok
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $accessToken = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $appId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $appSecret = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private ?int $nbrItems = 7;

    #[ORM\OneToOne(targetEntity: Api::class, mappedBy: 'tiktok', cascade: ['persist', 'remove'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Api $api = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(?string $appId): static
    {
        $this->appId = $appId;

        return $this;
    }

    public function getAppSecret(): ?string
    {
        return $this->appSecret;
    }

    public function setAppSecret(?string $appSecret): static
    {
        $this->appSecret = $appSecret;

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

    public function getApi(): ?Api
    {
        return $this->api;
    }

    public function setApi(?Api $api): static
    {
        // unset the owning side of the relation if necessary
        if ($api === null && $this->api !== null) {
            $this->api->setTikTok(null);
        }

        // set the owning side of the relation if necessary
        if ($api !== null && $api->getTikTok() !== $this) {
            $api->setTikTok($this);
        }

        $this->api = $api;

        return $this;
    }
}
