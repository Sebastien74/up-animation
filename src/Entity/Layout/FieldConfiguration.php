<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Repository\Layout\FieldConfigurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * FieldConfiguration.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_field_configuration')]
#[ORM\Entity(repositoryClass: FieldConfigurationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FieldConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $anonymize = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $constraints = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $preferredChoices = [];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $required = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $multiple = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $expanded = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $picker = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $inline = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $smallSize = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $regex = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $min = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $max = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxFileSize = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $filesTypes = [];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $buttonType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $script = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $className = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $masterField = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $associatedElements = [];

    #[ORM\OneToOne(targetEntity: Block::class, mappedBy: 'fieldConfiguration', cascade: ['persist', 'remove'])]
    private ?Block $block = null;

    #[ORM\OneToMany(targetEntity: FieldValue::class, mappedBy: 'configuration', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $fieldValues;

    /**
     * FieldConfiguration constructor.
     */
    public function __construct()
    {
        $this->fieldValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isAnonymize(): ?bool
    {
        return $this->anonymize;
    }

    public function setAnonymize(bool $anonymize): static
    {
        $this->anonymize = $anonymize;

        return $this;
    }

    public function getConstraints(): ?array
    {
        return $this->constraints;
    }

    public function setConstraints(?array $constraints): static
    {
        $this->constraints = $constraints;

        return $this;
    }

    public function getPreferredChoices(): ?array
    {
        return $this->preferredChoices;
    }

    public function setPreferredChoices(?array $preferredChoices): static
    {
        $this->preferredChoices = $preferredChoices;

        return $this;
    }

    public function isRequired(): ?bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): static
    {
        $this->required = $required;

        return $this;
    }

    public function isMultiple(): ?bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isExpanded(): ?bool
    {
        return $this->expanded;
    }

    public function setExpanded(bool $expanded): static
    {
        $this->expanded = $expanded;

        return $this;
    }

    public function isPicker(): ?bool
    {
        return $this->picker;
    }

    public function setPicker(bool $picker): static
    {
        $this->picker = $picker;

        return $this;
    }

    public function isInline(): ?bool
    {
        return $this->inline;
    }

    public function setInline(bool $inline): static
    {
        $this->inline = $inline;

        return $this;
    }

    public function isSmallSize(): ?bool
    {
        return $this->smallSize;
    }

    public function setSmallSize(bool $smallSize): static
    {
        $this->smallSize = $smallSize;

        return $this;
    }

    public function getRegex(): ?string
    {
        return $this->regex;
    }

    public function setRegex(?string $regex): static
    {
        $this->regex = $regex;

        return $this;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function setMin(?int $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function setMax(?int $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function getMaxFileSize(): ?int
    {
        return $this->maxFileSize;
    }

    public function setMaxFileSize(?int $maxFileSize): static
    {
        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    public function getFilesTypes(): ?array
    {
        return $this->filesTypes;
    }

    public function setFilesTypes(?array $filesTypes): static
    {
        $this->filesTypes = $filesTypes;

        return $this;
    }

    public function getButtonType(): ?string
    {
        return $this->buttonType;
    }

    public function setButtonType(?string $buttonType): static
    {
        $this->buttonType = $buttonType;

        return $this;
    }

    public function getScript(): ?string
    {
        return $this->script;
    }

    public function setScript(?string $script): static
    {
        $this->script = $script;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): static
    {
        $this->className = $className;

        return $this;
    }

    public function getMasterField(): ?string
    {
        return $this->masterField;
    }

    public function setMasterField(?string $masterField): static
    {
        $this->masterField = $masterField;

        return $this;
    }

    public function getAssociatedElements(): ?array
    {
        return $this->associatedElements;
    }

    public function setAssociatedElements(?array $associatedElements): static
    {
        $this->associatedElements = $associatedElements;

        return $this;
    }

    public function getBlock(): ?Block
    {
        return $this->block;
    }

    public function setBlock(?Block $block): static
    {
        // unset the owning side of the relation if necessary
        if (null === $block && null !== $this->block) {
            $this->block->setFieldConfiguration(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $block && $block->getFieldConfiguration() !== $this) {
            $block->setFieldConfiguration($this);
        }

        $this->block = $block;

        return $this;
    }

    /**
     * @return Collection<int, FieldValue>
     */
    public function getFieldValues(): Collection
    {
        return $this->fieldValues;
    }

    public function addFieldValue(FieldValue $fieldValue): static
    {
        if (!$this->fieldValues->contains($fieldValue)) {
            $this->fieldValues->add($fieldValue);
            $fieldValue->setConfiguration($this);
        }

        return $this;
    }

    public function removeFieldValue(FieldValue $fieldValue): static
    {
        if ($this->fieldValues->removeElement($fieldValue)) {
            // set the owning side to null (unless already changed)
            if ($fieldValue->getConfiguration() === $this) {
                $fieldValue->setConfiguration(null);
            }
        }

        return $this;
    }
}
