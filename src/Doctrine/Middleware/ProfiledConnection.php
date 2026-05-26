<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

final class ProfiledConnection extends AbstractConnectionMiddleware
{
    public function __construct(Connection $connection, private readonly QueryProfiler $profiler)
    {
        parent::__construct($connection);
    }

    public function prepare(string $sql): Statement
    {
        return new ProfiledStatement(parent::prepare($sql), $this->profiler);
    }

    public function query(string $sql): Result
    {
        $start = microtime(true);
        try {
            return parent::query($sql);
        } finally {
            $this->profiler->record((microtime(true) - $start) * 1000);
        }
    }

    public function exec(string $sql): int|string
    {
        $start = microtime(true);
        try {
            return parent::exec($sql);
        } finally {
            $this->profiler->record((microtime(true) - $start) * 1000);
        }
    }
}
