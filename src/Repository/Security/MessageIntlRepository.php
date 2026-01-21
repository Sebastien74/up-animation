<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\MessageIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MessageIntlRepository.
 *
 * @extends ServiceEntityRepository<MessageIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MessageIntlRepository extends ServiceEntityRepository
{
    /**
     * MessageIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, MessageIntl::class);
    }
}
