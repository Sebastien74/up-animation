<?php

declare(strict_types=1);

namespace App\Service\Development;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * PhpCliBinaryResolver.
 *
 * Resolves a usable PHP CLI binary for child processes launched from the web
 * context. Under WAMP / Apache mod_php, PHP_BINARY points to httpd.exe and is
 * unusable, so several fallbacks are attempted in order.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class PhpCliBinaryResolver
{
    public function resolve(): string
    {
        $override = getenv('PHP_CLI_PATH') ?: ($_ENV['PHP_CLI_PATH'] ?? null);
        if (is_string($override) && '' !== $override && is_file($override) && is_executable($override)) {
            return $override;
        }

        if (\PHP_BINARY && is_file(\PHP_BINARY)) {
            $name = strtolower(pathinfo(\PHP_BINARY, \PATHINFO_FILENAME));
            if ('php' === $name || str_starts_with($name, 'php-')) {
                return \PHP_BINARY;
            }
        }

        $iniPath = php_ini_loaded_file();
        if (is_string($iniPath) && '' !== $iniPath) {
            $candidate = dirname($iniPath).\DIRECTORY_SEPARATOR.('\\' === \DIRECTORY_SEPARATOR ? 'php.exe' : 'php');
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $finderPath = (new PhpExecutableFinder())->find(false);
        if (is_string($finderPath) && '' !== $finderPath && is_file($finderPath)) {
            $name = strtolower(pathinfo($finderPath, \PATHINFO_FILENAME));
            if ('php' === $name || str_starts_with($name, 'php-')) {
                return $finderPath;
            }
        }

        $fromPath = (new ExecutableFinder())->find('php');
        if (is_string($fromPath) && '' !== $fromPath) {
            return $fromPath;
        }

        throw new RuntimeException('Unable to locate a PHP CLI binary. Set PHP_CLI_PATH in your .env.local pointing to php.exe (eg. C:\\wamp64\\bin\\php\\php8.5.3\\php.exe).');
    }
}
