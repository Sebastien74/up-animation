<?php

declare(strict_types=1);

namespace App\Entity\Module\Table;

use App\Entity\BaseEntity;
use App\Repository\Module\Table\CellRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Cell.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_table_cell')]
#[ORM\Entity(repositoryClass: CellRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Cell extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'col';
    protected static array $interface = [
        'name' => 'tablecell',
        'search' => true,
    ];

    #[ORM\OneToMany(targetEntity: CellIntl::class, mappedBy: 'cell', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Col::class, cascade: ['persist'], inversedBy: 'cells')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Col $col = null;

    /**
     * Cell constructor.
     */
    public function __construct()
    {
        $this->intls = new ArrayCollection();
    }

    /**
     * @return Collection<int, CellIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(CellIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setCell($this);
        }

        return $this;
    }

    public function removeIntl(CellIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getCell() === $this) {
                $intl->setCell(null);
            }
        }

        return $this;
    }

    public function getCol(): ?Col
    {
        return $this->col;
    }

    public function setCol(?Col $col): static
    {
        $this->col = $col;

        return $this;
    }
}
