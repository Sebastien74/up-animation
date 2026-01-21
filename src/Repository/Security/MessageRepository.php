<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MessageIntlRepository.
 *
 * @extends ServiceEntityRepository<Message>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MessageRepository extends ServiceEntityRepository
{
    /**
     * MessageRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Message::class);
    }
}
