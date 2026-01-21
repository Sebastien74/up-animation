<?php

declare(strict_types=1);

namespace App\Entity\Module\Map;

use App\Entity\BaseIntl;
use App\Repository\Module\Map\CategoryIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_map_category_intls')]
#[ORM\Entity(repositoryClass: CategoryIntlRepository::class)]
class CategoryIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Category::class, cascade: ['persist'], inversedBy: 'intls')]
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
