<?php

declare(strict_types=1);

namespace App\Repository\Module\Contact;

use App\Entity\Module\Contact\ContactIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ContactIntlRepository.
 *
 * @extends ServiceEntityRepository<ContactIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContactIntlRepository extends ServiceEntityRepository
{
    /**
     * ContactIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ContactIntl::class);
    }
}
