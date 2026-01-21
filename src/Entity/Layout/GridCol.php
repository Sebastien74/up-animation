<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseEntity;
use App\Repository\Layout\GridColRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Grid.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_grid_col')]
#[ORM\Entity(repositoryClass: GridColRepository::class)]
#[ORM\HasLifecycleCallbacks]
class GridCol extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'grid';
    protected static array $interface = [
        'name' => 'gridcol',
    ];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $size = 12;

    #[ORM\ManyToOne(targetEntity: Grid::class, inversedBy: 'cols')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Grid $grid = null;

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getGrid(): ?Grid
    {
        return $this->grid;
    }

    public function setGrid(?Grid $grid): static
    {
        $this->grid = $grid;

        return $this;
    }
}
