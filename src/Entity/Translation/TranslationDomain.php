<?php

declare(strict_types=1);

namespace App\Entity\Translation;

use App\Entity\BaseInterface;
use App\Repository\Translation\TranslationDomainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * TranslationDomain.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'translation_domain')]
#[ORM\Entity(repositoryClass: TranslationDomainRepository::class)]
class TranslationDomain extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'translationdomain',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $adminName = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $extract = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $forTranslator = false;

    #[ORM\OneToMany(targetEntity: TranslationUnit::class, mappedBy: 'domain', orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $units;

    /**
     * TranslationDomain constructor.
     */
    public function __construct()
    {
        $this->units = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdminName(): ?string
    {
        return $this->adminName;
    }

    public function setAdminName(?string $adminName): static
    {
        $this->adminName = $adminName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isExtract(): ?bool
    {
        return $this->extract;
    }

    public function setExtract(bool $extract): static
    {
        $this->extract = $extract;

        return $this;
    }

    public function isForTranslator(): ?bool
    {
        return $this->forTranslator;
    }

    public function setForTranslator(bool $forTranslator): static
    {
        $this->forTranslator = $forTranslator;

        return $this;
    }

    /**
     * @return Collection<int, TranslationUnit>
     */
    public function getUnits(): Collection
    {
        return $this->units;
    }

    public function addUnit(TranslationUnit $unit): static
    {
        if (!$this->units->contains($unit)) {
            $this->units->add($unit);
            $unit->setDomain($this);
        }

        return $this;
    }

    public function removeUnit(TranslationUnit $unit): static
    {
        if ($this->units->removeElement($unit)) {
            // set the owning side to null (unless already changed)
            if ($unit->getDomain() === $this) {
                $unit->setDomain(null);
            }
        }

        return $this;
    }
}
