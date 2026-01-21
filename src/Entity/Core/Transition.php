<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseEntity;
use App\Repository\Core\TransitionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Transition.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_transition')]
#[ORM\Entity(repositoryClass: TransitionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Transition extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'configuration';
    protected static array $interface = [
        'name' => 'transition',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $section = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $element = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $laxPreset = [];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $aosEffect = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $animateEffect = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $duration = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $delay = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $offsetData = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $parameters = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $activeForBlock = false;

    #[ORM\ManyToOne(targetEntity: Configuration::class, inversedBy: 'transitions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Configuration $configuration = null;

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(?string $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getElement(): ?string
    {
        return $this->element;
    }

    public function setElement(?string $element): static
    {
        $this->element = $element;

        return $this;
    }

    public function getLaxPreset(): ?array
    {
        return $this->laxPreset;
    }

    public function setLaxPreset(?array $laxPreset): static
    {
        $this->laxPreset = $laxPreset;

        return $this;
    }

    public function getAosEffect(): ?string
    {
        return $this->aosEffect;
    }

    public function setAosEffect(?string $aosEffect): static
    {
        $this->aosEffect = $aosEffect;

        return $this;
    }

    public function getAnimateEffect(): ?string
    {
        return $this->animateEffect;
    }

    public function setAnimateEffect(?string $animateEffect): static
    {
        $this->animateEffect = $animateEffect;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDelay(): ?string
    {
        return $this->delay;
    }

    public function setDelay(?string $delay): static
    {
        $this->delay = $delay;

        return $this;
    }

    public function getOffsetData(): ?string
    {
        return $this->offsetData;
    }

    public function setOffsetData(?string $offsetData): static
    {
        $this->offsetData = $offsetData;

        return $this;
    }

    public function getParameters(): ?string
    {
        return $this->parameters;
    }

    public function setParameters(?string $parameters): static
    {
        $this->parameters = $parameters;

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

    public function isActiveForBlock(): ?bool
    {
        return $this->activeForBlock;
    }

    public function setActiveForBlock(bool $activeForBlock): static
    {
        $this->activeForBlock = $activeForBlock;

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
