<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Module\Form\StepFormIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * StepFormIntlRepository.
 *
 * @extends ServiceEntityRepository<StepFormIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class StepFormIntlRepository extends ServiceEntityRepository
{
    /**
     * StepFormIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, StepFormIntl::class);
    }
}
