<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseEntity;
use App\Repository\Core\ColorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Color.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_color')]
#[ORM\Entity(repositoryClass: ColorRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Color extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'configuration';
    protected static array $interface = [
        'name' => 'color',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    private ?string $color = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    private ?string $category = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $deletable = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    #[ORM\ManyToOne(targetEntity: Configuration::class, inversedBy: 'colors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Configuration $configuration = null;

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isDeletable(): ?bool
    {
        return $this->deletable;
    }

    public function setDeletable(bool $deletable): static
    {
        $this->deletable = $deletable;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
