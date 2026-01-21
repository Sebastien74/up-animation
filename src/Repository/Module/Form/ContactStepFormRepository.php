<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Module\Form\ContactStepForm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ContactStepFormRepository.
 *
 * @extends ServiceEntityRepository<ContactStepForm>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContactStepFormRepository extends ServiceEntityRepository
{
    /**
     * ContactStepFormRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ContactStepForm::class);
    }
}
