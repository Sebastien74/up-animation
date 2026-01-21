<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Repository\Security\UserRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * UserRequest.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_user_request')]
#[ORM\Entity(repositoryClass: UserRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $login = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    protected ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups('main')]
    protected ?string $lastName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups('main')]
    protected ?string $firstName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $tokenDate = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    protected ?string $locale = null;

    #[ORM\OneToOne(targetEntity: UserFront::class, mappedBy: 'userRequest')]
    private ?UserFront $userFront = null;

    /**
     * @throws Exception
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->tokenDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->createdAt = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    /**
     * @throws Exception
     */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenDate(): ?\DateTimeInterface
    {
        return $this->tokenDate;
    }

    public function setTokenDate(?\DateTimeInterface $tokenDate): static
    {
        $this->tokenDate = $tokenDate;

        return $this;
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

    public function getUserFront(): ?UserFront
    {
        return $this->userFront;
    }

    public function setUserFront(?UserFront $userFront): static
    {
        // unset the owning side of the relation if necessary
        if ($userFront === null && $this->userFront !== null) {
            $this->userFront->setUserRequest(null);
        }

        // set the owning side of the relation if necessary
        if ($userFront !== null && $userFront->getUserRequest() !== $this) {
            $userFront->setUserRequest($this);
        }

        $this->userFront = $userFront;

        return $this;
    }
}
