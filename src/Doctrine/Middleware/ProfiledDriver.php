<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final class ProfiledDriver extends AbstractDriverMiddleware
{
    public function __construct(Driver $driver, private readonly QueryProfiler $profiler)
    {
        parent::__construct($driver);
    }

    public function connect(array $params): Connection
    {
        return new ProfiledConnection(parent::connect($params), $this->profiler);
    }
}
