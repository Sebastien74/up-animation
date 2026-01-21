<?php

declare(strict_types=1);

namespace App\Entity\Translation;

use App\Entity\BaseInterface;
use App\Repository\Translation\TranslationUnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * TranslationUnit.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'translation_unit')]
#[ORM\Entity(repositoryClass: TranslationUnitRepository::class)]
class TranslationUnit extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'domain';
    protected static array $interface = [
        'name' => 'translationunit',
        'disabled_flash_bag' => true,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, options: ['collation' => 'utf8mb4_bin'])]
    private ?string $keyName = null;

    #[ORM\OneToMany(targetEntity: Translation::class, mappedBy: 'unit', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    private ArrayCollection|PersistentCollection $translations;

    #[ORM\ManyToOne(targetEntity: TranslationDomain::class, inversedBy: 'units')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private ?TranslationDomain $domain = null;

    /**
     * TranslationUnit constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyName(): ?string
    {
        return $this->keyName;
    }

    public function setKeyName(string $keyName): static
    {
        $this->keyName = $keyName;

        return $this;
    }

    /**
     * @return Collection<int, Translation>
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(Translation $translation): static
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setUnit($this);
        }

        return $this;
    }

    public function removeTranslation(Translation $translation): static
    {
        if ($this->translations->removeElement($translation)) {
            // set the owning side to null (unless already changed)
            if ($translation->getUnit() === $this) {
                $translation->setUnit(null);
            }
        }

        return $this;
    }

    public function getDomain(): ?TranslationDomain
    {
        return $this->domain;
    }

    public function setDomain(?TranslationDomain $domain): static
    {
        $this->domain = $domain;

        return $this;
    }
}
