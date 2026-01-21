<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Repository\Security\PictureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Picture.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_user_picture')]
#[ORM\Entity(repositoryClass: PictureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Picture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $dirname = null;

    #[ORM\OneToOne(targetEntity: User::class, mappedBy: 'picture', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\OneToOne(targetEntity: UserFront::class, mappedBy: 'picture', cascade: ['persist', 'remove'])]
    private ?UserFront $userFront = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getPath(): ?string
    {
        return str_replace('\\', '/', $this->dirname);
    }

    public function getDirname(): ?string
    {
        return $this->dirname;
    }

    public function setDirname(?string $dirname): static
    {
        $this->dirname = $dirname;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        // unset the owning side of the relation if necessary
        if (null === $user && null !== $this->user) {
            $this->user->setPicture(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $user && $user->getPicture() !== $this) {
            $user->setPicture($this);
        }

        $this->user = $user;

        return $this;
    }

    public function getUserFront(): ?UserFront
    {
        return $this->userFront;
    }

    public function setUserFront(?UserFront $userFront): static
    {
        // unset the owning side of the relation if necessary
        if (null === $userFront && null !== $this->userFront) {
            $this->userFront->setPicture(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $userFront && $userFront->getPicture() !== $this) {
            $userFront->setPicture($this);
        }

        $this->userFront = $userFront;

        return $this;
    }
}
