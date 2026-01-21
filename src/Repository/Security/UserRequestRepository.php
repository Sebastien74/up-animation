<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\UserRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * UserRequestRepository.
 *
 * @extends ServiceEntityRepository<UserRequest>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserRequestRepository extends ServiceEntityRepository
{
    /**
     * UserRequestRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, UserRequest::class);
    }
}
