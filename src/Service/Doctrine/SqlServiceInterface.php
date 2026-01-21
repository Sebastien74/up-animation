<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

/**
 * SqlServiceInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface SqlServiceInterface
{
    public function setConnection(string $manager): void;
    public function find(string $table, string $column, mixed $value): ?array;
    public function findAll(string $table, string $sort = 'id', string $order = 'ASC'): array;
    public function findBy(string $table, string $column, mixed $value, ?string $sort = null, ?string $order = null): array;
    public function prefix(): string|array|null;
    public function relationName(string $table, string $excluded): string|array|null;
}
