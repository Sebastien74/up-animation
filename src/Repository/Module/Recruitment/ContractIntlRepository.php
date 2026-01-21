<?php

declare(strict_types=1);

namespace App\Repository\Module\Recruitment;

use App\Entity\Module\Recruitment\ContractIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ContractIntlRepository.
 *
 * @extends ServiceEntityRepository<ContractIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContractIntlRepository extends ServiceEntityRepository
{
    /**
     * ContractIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ContractIntl::class);
    }
}
