<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Core\Website;
use App\Entity\Module\Form\StepForm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * StepFormRepository.
 *
 * @extends ServiceEntityRepository<StepForm>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class StepFormRepository extends ServiceEntityRepository
{
    /**
     * StepFormRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, StepForm::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?StepForm
    {
        $statement = $this->createQueryBuilder('sf')
            ->leftJoin('sf.website', 'w')
            ->andWhere('sf.website = :website')
            ->setParameter('website', $website)
            ->addSelect('w');

        if (is_numeric($filter)) {
            $statement->andWhere('sf.id = :id')
                ->setParameter('id', $filter);
        } else {
            $statement->andWhere('sf.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $statement->getQuery()
            ->getOneOrNullResult();
    }
}
