<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseEntity;
use App\Entity\Layout\Action;
use App\Repository\Core\ModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Module.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_module')]
#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Module extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'module',
    ];

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $role = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iconClass = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $inAdvert = false;

    #[ORM\OneToMany(targetEntity: Action::class, mappedBy: 'module', cascade: ['persist'])]
    private ArrayCollection|PersistentCollection $actions;

    #[ORM\OneToMany(targetEntity: ModuleIntl::class, mappedBy: 'module', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    /**
     * Module constructor.
     */
    public function __construct()
    {
        $this->actions = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getIconClass(): ?string
    {
        return $this->iconClass;
    }

    public function setIconClass(?string $iconClass): static
    {
        $this->iconClass = $iconClass;

        return $this;
    }

    public function isInAdvert(): ?bool
    {
        return $this->inAdvert;
    }

    public function setInAdvert(bool $inAdvert): static
    {
        $this->inAdvert = $inAdvert;

        return $this;
    }

    /**
     * @return Collection<int, Action>
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function addAction(Action $action): static
    {
        if (!$this->actions->contains($action)) {
            $this->actions->add($action);
            $action->setModule($this);
        }

        return $this;
    }

    public function removeAction(Action $action): static
    {
        if ($this->actions->removeElement($action)) {
            // set the owning side to null (unless already changed)
            if ($action->getModule() === $this) {
                $action->setModule(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ModuleIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(ModuleIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setModule($this);
        }

        return $this;
    }

    public function removeIntl(ModuleIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getModule() === $this) {
                $intl->setModule(null);
            }
        }

        return $this;
    }
}
