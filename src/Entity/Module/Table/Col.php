<?php

declare(strict_types=1);

namespace App\Entity\Module\Table;

use App\Entity\BaseEntity;
use App\Repository\Module\Table\ColRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Col.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_table_col')]
#[ORM\Entity(repositoryClass: ColRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Col extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'table';
    protected static array $interface = [
        'name' => 'tablecol',
        'search' => true,
    ];

    #[ORM\OneToMany(targetEntity: Cell::class, mappedBy: 'col', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $cells;

    #[ORM\OneToMany(targetEntity: ColIntl::class, mappedBy: 'col', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Table::class, cascade: ['persist'], inversedBy: 'cols')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Table $table = null;

    /**
     * Col constructor.
     */
    public function __construct()
    {
        $this->cells = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    /**
     * @return Collection<int, Cell>
     */
    public function getCells(): Collection
    {
        return $this->cells;
    }

    public function addCell(Cell $cell): static
    {
        if (!$this->cells->contains($cell)) {
            $this->cells->add($cell);
            $cell->setCol($this);
        }

        return $this;
    }

    public function removeCell(Cell $cell): static
    {
        if ($this->cells->removeElement($cell)) {
            // set the owning side to null (unless already changed)
            if ($cell->getCol() === $this) {
                $cell->setCol(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ColIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(ColIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setCol($this);
        }

        return $this;
    }

    public function removeIntl(ColIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getCol() === $this) {
                $intl->setCol(null);
            }
        }

        return $this;
    }

    public function getTable(): ?Table
    {
        return $this->table;
    }

    public function setTable(?Table $table): static
    {
        $this->table = $table;

        return $this;
    }
}
