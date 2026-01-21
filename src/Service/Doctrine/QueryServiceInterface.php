<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

/**
 * QueryServiceInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface QueryServiceInterface
{
    public function findOneBy(string $classname, string $column, mixed $value): ?object;

    public function findBy(string $classname, string $column, mixed $value): array;
}
