<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Module\Form\FormIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FormIntlRepository.
 *
 * @extends ServiceEntityRepository<FormIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormIntlRepository extends ServiceEntityRepository
{
    /**
     * FormIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FormIntl::class);
    }
}
