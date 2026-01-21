<?php

declare(strict_types=1);

namespace App\Entity\Module\Gallery;

use App\Entity\BaseMediaRelation;
use App\Repository\Module\Gallery\CategoryMediaRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryMediaRelation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_gallery_category_media_relations')]
#[ORM\Entity(repositoryClass: CategoryMediaRelationRepository::class)]
class CategoryMediaRelation extends BaseMediaRelation
{
    #[ORM\ManyToOne(targetEntity: Category::class, cascade: ['persist'], inversedBy: 'mediaRelations')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Category $category = null;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }
}
