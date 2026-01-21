<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Module\Form\ContactValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ContactValueRepository.
 *
 * @extends ServiceEntityRepository<ContactValue>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContactValueRepository extends ServiceEntityRepository
{
    /**
     * ContactValueRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ContactValue::class);
    }
}
