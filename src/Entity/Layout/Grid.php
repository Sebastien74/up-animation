<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseEntity;
use App\Repository\Layout\GridRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * Grid.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_grid')]
#[ORM\Entity(repositoryClass: GridRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Grid extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'grid',
    ];

    #[ORM\OneToMany(targetEntity: GridCol::class, mappedBy: 'grid', cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $cols;

    /**
     * Grid constructor.
     */
    public function __construct()
    {
        $this->cols = new ArrayCollection();
    }

    /**
     * @return Collection<int, GridCol>
     */
    public function getCols(): Collection
    {
        return $this->cols;
    }

    public function addCol(GridCol $col): static
    {
        if (!$this->cols->contains($col)) {
            $this->cols->add($col);
            $col->setGrid($this);
        }

        return $this;
    }

    public function removeCol(GridCol $col): static
    {
        if ($this->cols->removeElement($col)) {
            // set the owning side to null (unless already changed)
            if ($col->getGrid() === $this) {
                $col->setGrid(null);
            }
        }

        return $this;
    }
}
