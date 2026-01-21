<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseIntl;
use App\Repository\Security\UserCategoryIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserCategoryIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_user_category_intls')]
#[ORM\Entity(repositoryClass: UserCategoryIntlRepository::class)]
class UserCategoryIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: UserCategory::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?UserCategory $category = null;

    public function getCategory(): ?UserCategory
    {
        return $this->category;
    }

    public function setCategory(?UserCategory $category): static
    {
        $this->category = $category;

        return $this;
    }
}
