<?php

declare(strict_types=1);

namespace App\Service\Core;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * ScheduledCommandLogReader.
 *
 * Reads the tail of a scheduled command log file (var/log) so the admin can
 * inspect why a task failed or got locked, without shell access.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class ScheduledCommandLogReader
{
    private const DEFAULT_MAX_LINES = 60;

    private string $logDir;

    public function __construct(KernelInterface $kernel)
    {
        $this->logDir = rtrim($kernel->getLogDir(), '/\\').\DIRECTORY_SEPARATOR;
    }

    /**
     * Return the last lines of a command log file, oldest entries first.
     *
     * @return array<int, string>
     */
    public function tail(?string $logFile, int $maxLines = self::DEFAULT_MAX_LINES): array
    {
        $path = $this->resolvePath($logFile);
        if (null === $path) {
            return [];
        }

        $lines = array_map('rtrim', $this->readTail($path, $maxLines));

        return array_values(array_filter($lines, static fn (string $line): bool => '' !== $line));
    }

    /**
     * Resolve a safe, existing log path from a stored filename (basename only, inside the log dir).
     */
    private function resolvePath(?string $logFile): ?string
    {
        if (null === $logFile || '' === trim($logFile)) {
            return null;
        }

        $filename = basename(str_replace('\\', '/', $logFile));
        if ('' === $filename || !str_ends_with($filename, '.log')) {
            return null;
        }

        $path = $this->logDir.$filename;

        return is_file($path) && is_readable($path) ? $path : null;
    }

    /**
     * Read the last $maxLines lines by seeking backward, to avoid loading large logs in memory.
     *
     * @return array<int, string>
     */
    private function readTail(string $path, int $maxLines): array
    {
        $handle = fopen($path, 'rb');
        if (false === $handle) {
            return [];
        }

        $buffer = '';
        $chunkSize = 4096;
        fseek($handle, 0, \SEEK_END);
        $offset = (int) ftell($handle);

        try {
            while ($offset > 0 && substr_count($buffer, "\n") <= $maxLines) {
                $read = min($chunkSize, $offset);
                $offset -= $read;
                fseek($handle, $offset, \SEEK_SET);
                $buffer = (string) fread($handle, $read).$buffer;
            }
        } finally {
            fclose($handle);
        }

        $lines = preg_split('/\r\n|\r|\n/', $buffer) ?: [];

        return \count($lines) > $maxLines ? \array_slice($lines, -$maxLines) : $lines;
    }
}
