<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Kernel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        // Entities are persisted in Europe/Paris wall-clock; align the app default
        // timezone so Doctrine reads, KnpTime and Twig stay consistent (no UTC skew).
        date_default_timezone_set('Europe/Paris');

        parent::__construct($environment, $debug);
    }
}