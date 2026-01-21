<?php

declare(strict_types=1);

namespace App\Repository\Translation;

use App\Entity\Translation\TranslationUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TranslationUnitRepository.
 *
 * @extends ServiceEntityRepository<TranslationUnit>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TranslationUnitRepository extends ServiceEntityRepository
{
    /**
     * TranslationUnitRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, TranslationUnit::class);
    }
}
