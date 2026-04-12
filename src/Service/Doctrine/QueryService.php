<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

/**
 * QueryService.
 *
 * @doc https://www.doctrine-project.org/projects/doctrine-orm/en/2.17/reference/native-sql.html
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class QueryService implements QueryServiceInterface
{
    private array $cache = [];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Find one by.
     *
     * @throws NonUniqueResultException
     */
    public function findOneBy(string $classname, string $column, mixed $value): ?object
    {
        $cacheKey = 'findOneBy' . md5($classname . $column . serialize($value));
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from($classname, 'e')
            ->where('e.' . $column . ' = :value')
            ->setParameter('value', $value)
            ->setMaxResults(1);

        return $this->cache[$cacheKey] = $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find by.
     */
    public function findBy(string $classname, string $column, mixed $value): array
    {
        $cacheKey = 'findBy' . md5($classname . $column . serialize($value));
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $column = str_replace('_id', '', $column);

        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from($classname, 'e')
            ->where('e.' . $column . ' = :value')
            ->setParameter('value', $value);

        return $this->cache[$cacheKey] = $qb->getQuery()->getResult();
    }

    public function findFullEntity(int $id, string $classname): ?object
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from($classname, 'e')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id);

        $hasLayout = property_exists($classname, 'layout');
        if ($hasLayout) {
            $qb->leftJoin('e.layout', 'l')->addSelect('l');
        }

        $entity = $qb->getQuery()->getOneOrNullResult();

        if (!$entity) {
            return null;
        }

        if ($hasLayout && $layout = $entity->getLayout()) {
            $this->entityManager->createQueryBuilder()
                ->select('l', 'z', 'c', 'b')
                ->from(\App\Entity\Layout\Layout::class, 'l')
                ->leftJoin('l.zones', 'z')
                ->leftJoin('z.cols', 'c')
                ->leftJoin('c.blocks', 'b')
                ->andWhere('l.id = :layoutId')
                ->setParameter('layoutId', $layout->getId())
                ->getQuery()
                ->getResult();

            $this->entityManager->createQueryBuilder()
                ->select('b', 'bmr', 'bmrm', 'bmrmw', 'bi', 'bmri')
                ->from(\App\Entity\Layout\Block::class, 'b')
                ->leftJoin('b.mediaRelations', 'bmr')
                ->leftJoin('bmr.media', 'bmrm')
                ->leftJoin('bmrm.website', 'bmrmw')
                ->leftJoin('bmr.intl', 'bmri')
                ->leftJoin('b.intls', 'bi')
                ->innerJoin('b.col', 'c')
                ->innerJoin('c.zone', 'z')
                ->andWhere('z.layout = :layoutId')
                ->setParameter('layoutId', $layout->getId())
                ->getQuery()
                ->getResult();
        }

        if (property_exists($classname, 'urls')) {
            $this->entityManager->createQueryBuilder()
                ->select('e', 'u', 's')
                ->from($classname, 'e')
                ->leftJoin('e.urls', 'u')
                ->leftJoin('u.seo', 's')
                ->andWhere('e.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
        }

        if (property_exists($classname, 'mediaRelations')) {
            $this->entityManager->createQueryBuilder()
                ->select('e', 'mr', 'm', 'mw', 'mi')
                ->from($classname, 'e')
                ->leftJoin('e.mediaRelations', 'mr')
                ->leftJoin('mr.media', 'm')
                ->leftJoin('m.website', 'mw')
                ->leftJoin('mr.intl', 'mi')
                ->andWhere('e.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
        }

        if (property_exists($classname, 'intls')) {
            $this->entityManager->createQueryBuilder()
                ->select('e', 'ei')
                ->from($classname, 'e')
                ->leftJoin('e.intls', 'ei')
                ->andWhere('e.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
        }

        if (property_exists($classname, 'pages')) {
            $this->entityManager->createQueryBuilder()
                ->select('e', 'p')
                ->from($classname, 'e')
                ->leftJoin('e.pages', 'p')
                ->andWhere('e.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
        }

        if (property_exists($classname, 'website')) {
            $this->entityManager->createQueryBuilder()
                ->select('e', 'w', 'conf', 'colors', 'icons', 'transitions', 'cssClasses')
                ->from($classname, 'e')
                ->leftJoin('e.website', 'w')
                ->leftJoin('w.configuration', 'conf')
                ->leftJoin('conf.colors', 'colors')
                ->leftJoin('conf.icons', 'icons')
                ->leftJoin('conf.transitions', 'transitions')
                ->leftJoin('conf.cssClasses', 'cssClasses')
                ->andWhere('e.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
        }

        return $entity;
    }
}
