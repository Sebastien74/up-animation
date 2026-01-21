<?php

declare(strict_types=1);

namespace App\Entity\Module\Catalog;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Catalog\SubCategoryMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * SubCategoryMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_catalog_sub_category_media_relations')]
#[ORM\Entity(repositoryClass: SubCategoryMediaRelationRepository::class)]
class SubCategoryMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: SubCategory::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
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
