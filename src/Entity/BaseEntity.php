<?php

declare(strict_types=1);

namespace App\Entity;

use App\Service\Core\Urlizer;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * BaseEntity.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class BaseEntity extends BaseInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    protected ?string $adminName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $slug = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $computeETag = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $position = 1;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!$this->slug) {
            $this->slug = Urlizer::urlize($this->getAdminName());
        }

        parent::prePersist();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdminName(): ?string
    {
        return $this->adminName ? ltrim($this->adminName, '__') : $this->adminName;
    }

    public function setAdminName(?string $adminName): static
    {
        $this->adminName = $adminName ? ltrim($adminName, '__') : $adminName;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getComputeETag(): ?string
    {
        return $this->computeETag;
    }

    public function setComputeETag(?string $computeETag): static
    {
        $this->computeETag = $computeETag;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }
}
