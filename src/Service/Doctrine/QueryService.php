<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * QueryService.
 *
 * @doc https://www.doctrine-project.org/projects/doctrine-orm/en/2.17/reference/native-sql.html
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class QueryService implements QueryServiceInterface
{
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
        $metadata = $this->entityManager->getClassMetadata($classname);
        $table = !empty($metadata->table['name']) ? $metadata->table['name'] : null;

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult($classname, 'e');
        $columns = '';
        foreach ($metadata->fieldNames as $fieldName) {
            $rsm->addFieldResult('e', $fieldName, $fieldName);
            $columns .= $fieldName.', ';
        }
        $query = $this->entityManager->createNativeQuery('SELECT '.rtrim($columns, ', ').' FROM '.$table.' WHERE '.$column.' = :'.$column, $rsm);
        $query->setParameter(':'.$column, $value);

        return $query->getOneOrNullResult();
    }

    /**
     * Find by.
     */
    public function findBy(string $classname, string $column, mixed $value): array
    {
        $metadata = $this->entityManager->getClassMetadata($classname);
        $table = !empty($metadata->table['name']) ? $metadata->table['name'] : null;

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult($classname, 'e');
        $columns = '';
        foreach ($metadata->fieldNames as $fieldName) {
            $rsm->addFieldResult('e', $fieldName, $fieldName);
            $columns .= $fieldName.', ';
        }
        $query = $this->entityManager->createNativeQuery('SELECT '.rtrim($columns, ', ').' FROM '.$table.' WHERE '.$column.' = :'.$column, $rsm);
        $query->setParameter(':'.$column, $value);

        return $query->getResult();
    }
}
