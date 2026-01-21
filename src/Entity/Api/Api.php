<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Core\Website;
use App\Repository\Api\ApiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ApiModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api')]
#[ORM\Entity(repositoryClass: ApiRepository::class)]
class Api
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $addThis = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $tawkToId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $securitySecretKey = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $securitySecretIv = null;

    #[ORM\OneToOne(targetEntity: Facebook::class, inversedBy: 'api', cascade: ['persist', 'remove'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Facebook $facebook = null;

    #[ORM\OneToOne(targetEntity: Google::class, inversedBy: 'api', cascade: ['persist', 'remove'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Google $google = null;

    #[ORM\OneToOne(targetEntity: Instagram::class, inversedBy: 'api', cascade: ['persist', 'remove'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Instagram $instagram = null;

    #[ORM\OneToOne(targetEntity: Custom::class, inversedBy: 'api', cascade: ['persist', 'remove'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Custom $custom = null;

    #[ORM\OneToOne(targetEntity: Website::class, mappedBy: 'api')]
    private ?Website $website = null;

    /**
     * @throws Exception
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $securitySecretIv = base64_encode(uniqid().password_hash(uniqid(), PASSWORD_BCRYPT).random_bytes(10));
        $this->securitySecretIv = substr(str_shuffle($securitySecretIv), 0, 45);
        $securitySecretKey = base64_encode(uniqid().password_hash(uniqid(), PASSWORD_BCRYPT).random_bytes(10));
        $this->securitySecretKey = substr(str_shuffle($securitySecretKey), 0, 45);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddThis(): ?string
    {
        return $this->addThis;
    }

    public function setAddThis(?string $addThis): static
    {
        $this->addThis = $addThis;

        return $this;
    }

    public function getTawkToId(): ?string
    {
        return $this->tawkToId;
    }

    public function setTawkToId(?string $tawkToId): static
    {
        $this->tawkToId = $tawkToId;

        return $this;
    }

    public function getSecuritySecretKey(): ?string
    {
        return $this->securitySecretKey;
    }

    public function setSecuritySecretKey(?string $securitySecretKey): static
    {
        $this->securitySecretKey = $securitySecretKey;

        return $this;
    }

    public function getSecuritySecretIv(): ?string
    {
        return $this->securitySecretIv;
    }

    public function setSecuritySecretIv(?string $securitySecretIv): static
    {
        $this->securitySecretIv = $securitySecretIv;

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

    public function getGoogle(): ?Google
    {
        return $this->google;
    }

    public function setGoogle(?Google $google): static
    {
        $this->google = $google;

        return $this;
    }

    public function getInstagram(): ?Instagram
    {
        return $this->instagram;
    }

    public function setInstagram(?Instagram $instagram): static
    {
        $this->instagram = $instagram;

        return $this;
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

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        // unset the owning side of the relation if necessary
        if (null === $website && null !== $this->website) {
            $this->website->setApi(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $website && $website->getApi() !== $this) {
            $website->setApi($this);
        }

        $this->website = $website;

        return $this;
    }
}
