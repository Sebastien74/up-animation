<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseIntl;
use App\Repository\Module\Catalog\CatalogIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CatalogIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_intls')]
#[ORM\Entity(repositoryClass: CatalogIntlRepository::class)]
class CatalogIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Catalog::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Catalog $catalog = null;

    public function getCatalog(): ?Catalog
    {
        return $this->catalog;
    }

    public function setCatalog(?Catalog $catalog): static
    {
        $this->catalog = $catalog;

        return $this;
    }
}
