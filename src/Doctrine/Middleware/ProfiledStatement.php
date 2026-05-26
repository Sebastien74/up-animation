<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

final class ProfiledStatement extends AbstractStatementMiddleware
{
    public function __construct(Statement $statement, private readonly QueryProfiler $profiler)
    {
        parent::__construct($statement);
    }

    public function execute(): Result
    {
        $start = microtime(true);
        try {
            return parent::execute();
        } finally {
            $this->profiler->record((microtime(true) - $start) * 1000);
        }
    }
}
