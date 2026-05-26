<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/**
 * QueryProfilerMiddleware.
 *
 * Lightweight DBAL middleware that counts every executed SQL query and accumulates
 * their wall-clock duration into a shared QueryProfiler service. Used to expose
 * a Server-Timing header without depending on the WebProfilerBundle.
 *
 * Tag doctrine.middleware is auto-applied by doctrine-bundle autoconfig on any
 * class implementing Doctrine\DBAL\Driver\Middleware.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class QueryProfilerMiddleware implements Middleware
{
    public function __construct(private QueryProfiler $profiler)
    {
    }

    public function wrap(Driver $driver): Driver
    {
        return new ProfiledDriver($driver, $this->profiler);
    }
}
