<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SqlService.
 *
 * To get SQL data from current connection
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SqlService implements SqlServiceInterface
{
    private Connection $connection;
    private ?string $dbPrefix;

    /**
     * SqlService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $doctrine,
        private readonly string $prefix,
    ) {
        $this->connection = $this->entityManager->getConnection();
        $this->dbPrefix = $this->prefix;
    }

    /**
     * To set connection.
     */
    public function setConnection(string $manager): void
    {
        $this->dbPrefix = null;
        $entityManager = $this->doctrine->getManager($manager);
        $this->connection = $entityManager->getConnection();
    }

    /**
     * Find one in table.
     */
    public function find(string $table, string $column, mixed $value): array
    {
        try {
            $asClassname = str_contains($table, 'App\\Entity\\');
            $metadata = $asClassname ? $this->entityManager->getClassMetadata($table) : [];
            $table = $asClassname && !empty($metadata->table['name']) ? $metadata->table['name'] : ($this->dbPrefix ? $this->dbPrefix.'_'.$table : $table);
            $schemaManager = $this->connection->createSchemaManager();
            if ($schemaManager->tablesExist([$table]) && $value) {
                if (is_numeric($value)) {
                    $statement = $this->connection->prepare('SELECT * FROM '.$table.' WHERE '.$column.' = '.$value);
                } else {
                    $statement = $this->connection->prepare('SELECT * FROM '.$table.' WHERE '.$column.' = "'.$value.'"');
                }
                $result = $statement->executeQuery()->fetchAllAssociative();

                return !empty($result[0]) ? $result[0] : [];
            }
        } catch (\Exception $exception) {
            return ['exception' => $exception->getMessage()];
        }

        return [];
    }

    /**
     * Find all in table.
     */
    public function findAll(string $table, string $sort = 'id', string $order = 'ASC'): array
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if ($schemaManager->tablesExist([$table])) {
                $statement = $this->connection->prepare('SELECT * FROM '.$table.' ORDER BY '.$sort.' '.$order);

                return $statement->executeQuery()->fetchAllAssociative();
            }
        } catch (\Exception $exception) {
            return ['exception' => $exception->getMessage()];
        }

        return [];
    }

    /**
     * Find all in table.
     */
    public function findBy(string $table, string $column, mixed $value, ?string $sort = null, ?string $order = null): array
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if ($schemaManager->tablesExist([$table])) {
                if (is_numeric($value) && $sort && $order) {
                    $statement = $this->connection->prepare('SELECT * FROM '.$table.' WHERE '.$column.' = '.$value.' ORDER BY '.$sort.' '.$order);
                } elseif ($sort && $order) {
                    $statement = $this->connection->prepare('SELECT * FROM '.$table.' WHERE '.$column.' = "'.$value.'" ORDER BY '.$sort.' '.$order);
                } elseif (is_numeric($value)) {
                    $statement = $this->connection->prepare('SELECT * FROM '.$table.' WHERE '.$column.' = '.$value);
                } else {
                    $statement = $this->connection->prepare('SELECT * FROM '.$table.' WHERE '.$column.' = "'.$value.'"');
                }

                return $statement->executeQuery()->fetchAllAssociative();
            }
        } catch (\Exception $exception) {
            return ['exception' => $exception->getMessage()];
        }

        return [];
    }

    /**
     * Find DB prefix.
     */
    public function prefix(): string|array|null
    {
        try {
            $tables = $this->connection->createSchemaManager()->listTableNames();
            $firstTable = reset($tables);
            $matches = explode('_', $firstTable);
            return $matches[0];
        } catch (\Exception $exception) {
            return ['exception' => $exception->getMessage()];
        }
    }

    /**
     * Find DB prefix.
     */
    public function relationName(string $table, string $excluded): string|array|null
    {
        try {
            $columns = $this->connection->createSchemaManager()->listTableColumns($table);
            foreach ($columns as $name => $value) {
                if (!str_contains($name, $excluded)) {
                    return $name;
                }
            }
        } catch (\Exception $exception) {
            return ['exception' => $exception->getMessage()];
        }

        return null;
    }
}
