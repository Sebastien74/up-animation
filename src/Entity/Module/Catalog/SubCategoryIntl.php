<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseIntl;
use App\Repository\Module\Catalog\SubCategoryIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * SubCategoryIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_sub_category_intls')]
#[ORM\Entity(repositoryClass: SubCategoryIntlRepository::class)]
class SubCategoryIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: SubCategory::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?SubCategory $subCategory = null;

    public function getSubCategory(): ?SubCategory
    {
        return $this->subCategory;
    }

    public function setSubCategory(?SubCategory $subCategory): static
    {
        $this->subCategory = $subCategory;

        return $this;
    }
}
