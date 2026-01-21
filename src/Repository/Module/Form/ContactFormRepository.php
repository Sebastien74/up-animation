<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Module\Form\ContactForm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ContactFormRepository.
 *
 * @extends ServiceEntityRepository<ContactForm>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContactFormRepository extends ServiceEntityRepository
{
    /**
     * ContactFormRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ContactForm::class);
    }
}
