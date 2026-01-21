<?php

declare(strict_types=1);

namespace App\Entity\Module\Table;

use App\Entity\BaseIntl;
use App\Repository\Module\Table\TableIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TableIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_table_intls')]
#[ORM\Entity(repositoryClass: TableIntlRepository::class)]
class TableIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Table::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Table $table = null;

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
