<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseEntity;
use App\Entity\Core\Module;
use App\Repository\Layout\ActionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Action.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_action')]
#[ORM\Entity(repositoryClass: ActionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Action extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'action',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $controller = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $action = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $entity = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $card = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iconClass = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $dropdown = false;

    #[ORM\ManyToOne(targetEntity: Module::class, inversedBy: 'actions')]
    private ?Module $module = null;

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function setController(?string $controller): static
    {
        $this->controller = $controller;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(?string $entity): static
    {
        $this->entity = $entity;

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

    public function isCard(): ?bool
    {
        return $this->card;
    }

    public function setCard(bool $card): static
    {
        $this->card = $card;

        return $this;
    }

    public function getIconClass(): ?string
    {
        if ($this->iconClass && str_contains($this->iconClass, '.svg')) {
            $matches = explode(' ', $this->iconClass);
            $match = $matches[0];
            $this->iconClass = str_replace(['/', '.svg'], [' ', ''], $match);
        }

        return $this->iconClass;
    }

    public function setIconClass(?string $iconClass): static
    {
        $this->iconClass = $iconClass;

        return $this;
    }

    public function isDropdown(): ?bool
    {
        return $this->dropdown;
    }

    public function setDropdown(bool $dropdown): static
    {
        $this->dropdown = $dropdown;

        return $this;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): static
    {
        $this->module = $module;

        return $this;
    }
}
