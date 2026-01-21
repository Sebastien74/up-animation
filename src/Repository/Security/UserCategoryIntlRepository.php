<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\UserCategoryIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * UserCategoryIntlRepository.
 *
 * @extends ServiceEntityRepository<UserCategoryIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserCategoryIntlRepository extends ServiceEntityRepository
{
    /**
     * UserCategoryIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, UserCategoryIntl::class);
    }
}
