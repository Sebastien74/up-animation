<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseEntity;
use App\Repository\Layout\FieldValueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * FieldValue.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_field_value')]
#[ORM\Entity(repositoryClass: FieldValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FieldValue extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'configuration';
    protected static array $interface = [
        'name' => 'fieldvalue',
    ];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $associatedElements = [];

    #[ORM\OneToMany(targetEntity: FieldValueIntl::class, mappedBy: 'fieldValue', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\OneToMany(targetEntity: FieldValue::class, mappedBy: 'value')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ArrayCollection|PersistentCollection $values;

    #[ORM\ManyToOne(targetEntity: FieldConfiguration::class, inversedBy: 'fieldValues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FieldConfiguration $configuration = null;

    #[ORM\ManyToOne(targetEntity: FieldValue::class, inversedBy: 'values')]
    #[ORM\JoinColumn(name: 'value_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private ?FieldValue $value = null;

    /**
     * FieldValue constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
        $this->values = new ArrayCollection();
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

    /**
     * @return Collection<int, FieldValueIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(FieldValueIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setFieldValue($this);
        }

        return $this;
    }

    public function removeIntl(FieldValueIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getFieldValue() === $this) {
                $intl->setFieldValue(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FieldValue>
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    public function addValue(FieldValue $value): static
    {
        if (!$this->values->contains($value)) {
            $this->values->add($value);
            $value->setValue($this);
        }

        return $this;
    }

    public function removeValue(FieldValue $value): static
    {
        if ($this->values->removeElement($value)) {
            // set the owning side to null (unless already changed)
            if ($value->getValue() === $this) {
                $value->setValue(null);
            }
        }

        return $this;
    }

    public function getConfiguration(): ?FieldConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?FieldConfiguration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getValue(): ?self
    {
        return $this->value;
    }

    public function setValue(?self $value): static
    {
        $this->value = $value;

        return $this;
    }
}
