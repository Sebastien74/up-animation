<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\FacebookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Facebook.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_facebook')]
#[ORM\Entity(repositoryClass: FacebookRepository::class)]
class Facebook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $apiVersion = 'v3.1';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $pageId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $appId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $apiSecretKey = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $apiPublicKey = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $apiGraphVersion = null;

    #[ORM\OneToOne(targetEntity: Api::class, mappedBy: 'facebook', cascade: ['persist', 'remove'])]
    private ?Api $api = null;

    #[ORM\OneToMany(targetEntity: FacebookIntl::class, mappedBy: 'facebook', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    /**
     * Facebook constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiVersion(): ?string
    {
        return $this->apiVersion;
    }

    public function setApiVersion(string $apiVersion): static
    {
        $this->apiVersion = $apiVersion;

        return $this;
    }

    public function getPageId(): ?string
    {
        return $this->pageId;
    }

    public function setPageId(?string $pageId): static
    {
        $this->pageId = $pageId;

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

    public function getApiSecretKey(): ?string
    {
        return $this->apiSecretKey;
    }

    public function setApiSecretKey(?string $apiSecretKey): static
    {
        $this->apiSecretKey = $apiSecretKey;

        return $this;
    }

    public function getApiPublicKey(): ?string
    {
        return $this->apiPublicKey;
    }

    public function setApiPublicKey(?string $apiPublicKey): static
    {
        $this->apiPublicKey = $apiPublicKey;

        return $this;
    }

    public function getApiGraphVersion(): ?string
    {
        return $this->apiGraphVersion;
    }

    public function setApiGraphVersion(?string $apiGraphVersion): static
    {
        $this->apiGraphVersion = $apiGraphVersion;

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
            $this->api->setFacebook(null);
        }

        // set the owning side of the relation if necessary
        if ($api !== null && $api->getFacebook() !== $this) {
            $api->setFacebook($this);
        }

        $this->api = $api;

        return $this;
    }

    /**
     * @return Collection<int, FacebookIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(FacebookIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setFacebook($this);
        }

        return $this;
    }

    public function removeIntl(FacebookIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getFacebook() === $this) {
                $intl->setFacebook(null);
            }
        }

        return $this;
    }
}
