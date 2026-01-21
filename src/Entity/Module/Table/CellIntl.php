<?php

declare(strict_types=1);

namespace App\Entity\Module\Table;

use App\Entity\BaseIntl;
use App\Repository\Module\Table\CellIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CellIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_table_cell_intls')]
#[ORM\Entity(repositoryClass: CellIntlRepository::class)]
class CellIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Cell::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Cell $cell = null;

    public function getCell(): ?Cell
    {
        return $this->cell;
    }

    public function setCell(?Cell $cell): static
    {
        $this->cell = $cell;

        return $this;
    }
}
