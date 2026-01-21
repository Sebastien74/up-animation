<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Core\Website;
use App\Entity\Module\Form\Form;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * FormRepository.
 *
 * @extends ServiceEntityRepository<Form>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormRepository extends ServiceEntityRepository
{
    /**
     * FormRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Form::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Form
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->leftJoin('f.website', 'w')
            ->leftJoin('w.configuration', 'co')
            ->leftJoin('f.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->leftJoin('b.fieldConfiguration', 'fc')
            ->leftJoin('b.blockType', 'bt')
            ->leftJoin('b.intls', 'bi')
            ->leftJoin('b.actionIntls', 'bai')
            ->leftJoin('fc.fieldValues', 'fcv')
            ->leftJoin('fcv.intls', 'fcvi')
            ->andWhere('f.website = :website')
            ->setParameter('website', $website)
            ->addSelect('w')
            ->addSelect('co')
            ->addSelect('l')
            ->addSelect('z')
            ->addSelect('c')
            ->addSelect('b')
            ->addSelect('fc')
            ->addSelect('bt')
            ->addSelect('bi')
            ->addSelect('bai')
            ->addSelect('fcv')
            ->addSelect('fcvi');

        if (is_numeric($filter)) {
            $queryBuilder->andWhere('f.id = :id')
                ->setParameter('id', $filter);
        } else {
            $queryBuilder->andWhere('f.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Find admin index.
     *
     * @return array<Form>
     */
    public function findAdminIndex(Website $website, Request $request): array
    {
        $statement = $this->createQueryBuilder('f')
            ->leftJoin('f.website', 'w')
            ->andWhere('f.website = :website')
            ->setParameter('website', $website)
            ->addSelect('w');

        $stepForm = $request->get('stepform');

        if ($stepForm) {
            $statement->andWhere('f.stepform = :stepform')
                ->setParameter('stepform', $stepForm);
        } else {
            $statement->andWhere('f.stepform IS NULL');
        }

        return $statement->orderBy('f.position', 'ASC')
            ->getQuery()->getResult();
    }
}
