<?php

declare(strict_types=1);

namespace App\Entity\Module\Table;

use App\Entity\BaseIntl;
use App\Repository\Module\Table\ColIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ColIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_table_col_intls')]
#[ORM\Entity(repositoryClass: ColIntlRepository::class)]
class ColIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Col::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Col $col = null;

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
