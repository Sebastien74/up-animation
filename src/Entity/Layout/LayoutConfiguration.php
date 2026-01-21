<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseEntity;
use App\Entity\Core\Module;
use App\Entity\Core\Website;
use App\Repository\Layout\LayoutConfigurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * LayoutConfiguration.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_configuration')]
#[ORM\Entity(repositoryClass: LayoutConfigurationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'blockTypes',
        joinColumns: [new ORM\JoinColumn(name: 'configuration_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'block_type_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'layout_configuration_block_types')
    ),
    new ORM\AssociationOverride(
        name: 'modules',
        joinColumns: [new ORM\JoinColumn(name: 'configuration_id', referencedColumnName: 'id', onDelete: 'cascade')],
        inverseJoinColumns: [new ORM\InverseJoinColumn(name: 'module_id', referencedColumnName: 'id', onDelete: 'cascade')],
        joinTable: new ORM\JoinTable(name: 'layout_configuration_modules')
    ),
])]
class LayoutConfiguration extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'layoutconfiguration',
    ];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $entity = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $blockMarginBottom = 'mb-md';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $titleMarginBottom = 'mb-xs';

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    #[ORM\ManyToMany(targetEntity: BlockType::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $blockTypes;

    #[ORM\ManyToMany(targetEntity: Module::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $modules;

    /**
     * LayoutConfiguration constructor.
     */
    public function __construct()
    {
        $this->blockTypes = new ArrayCollection();
        $this->modules = new ArrayCollection();
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

    public function getBlockMarginBottom(): ?string
    {
        return $this->blockMarginBottom;
    }

    public function setBlockMarginBottom(?string $blockMarginBottom): static
    {
        $this->blockMarginBottom = $blockMarginBottom;

        return $this;
    }

    public function getTitleMarginBottom(): ?string
    {
        return $this->titleMarginBottom;
    }

    public function setTitleMarginBottom(?string $titleMarginBottom): static
    {
        $this->titleMarginBottom = $titleMarginBottom;

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return Collection<int, BlockType>
     */
    public function getBlockTypes(): Collection
    {
        return $this->blockTypes;
    }

    public function addBlockType(BlockType $blockType): static
    {
        if (!$this->blockTypes->contains($blockType)) {
            $this->blockTypes->add($blockType);
        }

        return $this;
    }

    public function removeBlockType(BlockType $blockType): static
    {
        $this->blockTypes->removeElement($blockType);

        return $this;
    }

    /**
     * @return Collection<int, Module>
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(Module $module): static
    {
        if (!$this->modules->contains($module)) {
            $this->modules->add($module);
        }

        return $this;
    }

    public function removeModule(Module $module): static
    {
        $this->modules->removeElement($module);

        return $this;
    }
}
