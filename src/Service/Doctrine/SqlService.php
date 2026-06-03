<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * SqlService.
 *
 * Low-level helpers around the active Doctrine connection. Identifiers
 * (table, column, sort) are validated against the schema and quoted; the
 * compared value is always passed as a bound parameter.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SqlService implements SqlServiceInterface
{
    private const string IDENTIFIER_REGEX = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    private Connection $connection;
    private ?string $dbPrefix;

    /**
     * SqlService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $doctrine,
        private readonly string $prefix,
        private readonly LoggerInterface $logger,
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
            $metadata = $asClassname ? $this->entityManager->getClassMetadata($table) : null;
            $table = $asClassname && !empty($metadata->table['name'])
                ? $metadata->table['name']
                : ($this->dbPrefix ? $this->dbPrefix.'_'.$table : $table);

            $schemaManager = $this->connection->createSchemaManager();
            if (!$schemaManager->tablesExist([$table]) || null === $value) {
                return [];
            }

            if (!$this->isValidColumn($table, $column)) {
                return [];
            }

            $sql = sprintf(
                'SELECT * FROM %s WHERE %s = :value',
                $this->connection->quoteSingleIdentifier($table),
                $this->connection->quoteSingleIdentifier($column)
            );
            $result = $this->connection->executeQuery($sql, ['value' => $value])->fetchAllAssociative();

            return !empty($result[0]) ? $result[0] : [];
        } catch (\Exception $exception) {
            $this->logger->error('SqlService query failed: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Find all in table.
     */
    public function findAll(string $table, string $sort = 'id', string $order = 'ASC'): array
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!$schemaManager->tablesExist([$table]) || !$this->isValidColumn($table, $sort)) {
                return [];
            }

            $sql = sprintf(
                'SELECT * FROM %s ORDER BY %s %s',
                $this->connection->quoteSingleIdentifier($table),
                $this->connection->quoteSingleIdentifier($sort),
                $this->normalizeOrder($order)
            );

            return $this->connection->executeQuery($sql)->fetchAllAssociative();
        } catch (\Exception $exception) {
            $this->logger->error('SqlService query failed: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Find all in table.
     */
    public function findBy(string $table, string $column, mixed $value, ?string $sort = null, ?string $order = null): array
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!$schemaManager->tablesExist([$table]) || !$this->isValidColumn($table, $column)) {
                return [];
            }

            $sql = sprintf(
                'SELECT * FROM %s WHERE %s = :value',
                $this->connection->quoteSingleIdentifier($table),
                $this->connection->quoteSingleIdentifier($column)
            );

            if ($sort && $order && $this->isValidColumn($table, $sort)) {
                $sql .= sprintf(
                    ' ORDER BY %s %s',
                    $this->connection->quoteSingleIdentifier($sort),
                    $this->normalizeOrder($order)
                );
            }

            return $this->connection->executeQuery($sql, ['value' => $value])->fetchAllAssociative();
        } catch (\Exception $exception) {
            $this->logger->error('SqlService query failed: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Find DB prefix.
     */
    public function prefix(): string|array|null
    {
        try {
            $tables = $this->connection->createSchemaManager()->introspectTableNames();
            $firstTable = reset($tables);
            $matches = explode('_', $firstTable ? $firstTable->toString() : '');
            return $matches[0];
        } catch (\Exception $exception) {
            $this->logger->error('SqlService query failed: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Find DB prefix.
     */
    public function relationName(string $table, string $excluded): string|array|null
    {
        try {
            $columns = $this->connection->createSchemaManager()->introspectTableColumnsByUnquotedName($table);
            foreach ($columns as $column) {
                $name = $column->getObjectName()->toString();
                if (!str_contains($name, $excluded)) {
                    return $name;
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('SqlService query failed: '.$exception->getMessage());

            return [];
        }

        return null;
    }

    private function isValidColumn(string $table, string $column): bool
    {
        if (!preg_match(self::IDENTIFIER_REGEX, $column)) {
            return false;
        }

        try {
            $columns = $this->connection->createSchemaManager()->introspectTableColumnsByUnquotedName($table);
        } catch (\Exception) {
            return false;
        }

        $names = [];
        foreach ($columns as $tableColumn) {
            $names[strtolower($tableColumn->getObjectName()->toString())] = true;
        }

        return array_key_exists(strtolower($column), $names);
    }

    private function normalizeOrder(?string $order): string
    {
        return strcasecmp((string) $order, 'DESC') === 0 ? 'DESC' : 'ASC';
    }
}
