<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseIntl;
use App\Repository\Module\Catalog\CatalogIntlRepository;
use Doctrine\DBAL\Types\Types;
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
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $label = null;

    #[ORM\ManyToOne(targetEntity: Catalog::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Catalog $catalog = null;

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

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
