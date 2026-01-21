<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseEntity;
use App\Repository\Layout\BlockTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BlockType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_block_type')]
#[ORM\Entity(repositoryClass: BlockTypeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class BlockType extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'blocktype',
    ];

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private ?string $category = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $iconClass = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $fieldType = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $dropdown = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $editable = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $inAdvert = false;

    #[ORM\OneToMany(targetEntity: BlockTypeIntl::class, mappedBy: 'blockType', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    /**
     * BlockType constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

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

    public function getFieldType(): ?string
    {
        return $this->fieldType;
    }

    public function setFieldType(?string $fieldType): static
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

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

    public function isEditable(): ?bool
    {
        return $this->editable;
    }

    public function setEditable(bool $editable): static
    {
        $this->editable = $editable;

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
     * @return Collection<int, BlockTypeIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(BlockTypeIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setBlockType($this);
        }

        return $this;
    }

    public function removeIntl(BlockTypeIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getBlockType() === $this) {
                $intl->setBlockType(null);
            }
        }

        return $this;
    }
}
