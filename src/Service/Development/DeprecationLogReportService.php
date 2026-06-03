<?php

declare(strict_types=1);

namespace App\Service\Development;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * DeprecationLogReportService.
 *
 * Reads and aggregates the deprecation log (var/log/<env>.deprecations.log) so the
 * internal team can track Symfony/library deprecations and prepare a major upgrade,
 * without shell access. Tail-reads the file to avoid loading large logs in memory.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class DeprecationLogReportService
{
    private const int DEFAULT_MAX_LINES = 5000;
    private const string LINE_PATTERN = '/^\[(?<date>[^\]]+)\] (?<channel>[\w.\-]+)\.(?<level>[A-Z]+): (?<message>.*)$/';
    private const string PACKAGE_PATTERN = '/Since (?<package>[\w\/.\-]+) [\d.]+:/';

    private string $path;

    public function __construct(private KernelInterface $kernel)
    {
        $this->path = rtrim($kernel->getLogDir(), '/\\').\DIRECTORY_SEPARATOR.$kernel->getEnvironment().'.deprecations.log';
    }

    /**
     * Aggregate the deprecation log into a dashboard-friendly projection.
     *
     * @return array{
     *     available: bool,
     *     file: string,
     *     environment: string,
     *     total: int,
     *     unique: int,
     *     scannedLines: int,
     *     truncated: bool,
     *     byPackage: array<int, array{package: string, count: int}>,
     *     items: array<int, array{message: string, package: string, count: int, lastSeen: ?string}>
     * }
     */
    public function getReport(int $maxLines = self::DEFAULT_MAX_LINES): array
    {
        $base = [
            'available' => is_file($this->path) && is_readable($this->path),
            'file' => basename($this->path),
            'environment' => $this->kernel->getEnvironment(),
            'total' => 0,
            'unique' => 0,
            'scannedLines' => 0,
            'truncated' => false,
            'byPackage' => [],
            'items' => [],
        ];

        if (!$base['available']) {
            return $base;
        }

        [$lines, $truncated] = $this->readTail($maxLines);
        $base['scannedLines'] = \count($lines);
        $base['truncated'] = $truncated;

        $grouped = [];
        $perPackage = [];
        foreach ($lines as $line) {
            $entry = $this->parseLine($line);
            if (null === $entry) {
                continue;
            }
            ++$base['total'];

            $key = $entry['message'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['message' => $entry['message'], 'package' => $entry['package'], 'count' => 0, 'lastSeen' => null];
            }
            ++$grouped[$key]['count'];
            $grouped[$key]['lastSeen'] = $entry['date'];

            $perPackage[$entry['package']] = ($perPackage[$entry['package']] ?? 0) + 1;
        }

        usort($grouped, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        arsort($perPackage);

        $base['items'] = array_values($grouped);
        $base['unique'] = \count($grouped);
        $base['byPackage'] = array_map(
            static fn (string $package, int $count): array => ['package' => $package, 'count' => $count],
            array_keys($perPackage),
            array_values($perPackage),
        );

        return $base;
    }

    public function clear(): bool
    {
        if (!is_file($this->path)) {
            return true;
        }

        // Truncate rather than unlink: the file stays open for Monolog to keep
        // appending in the same request (deleting it races on Windows/WAMP).
        return false !== @file_put_contents($this->path, '');
    }

    /**
     * @return array{0: array<int, string>, 1: bool} lines (oldest first) and whether the file was truncated
     */
    private function readTail(int $maxLines): array
    {
        $handle = fopen($this->path, 'rb');
        if (false === $handle) {
            return [[], false];
        }

        $buffer = '';
        $chunkSize = 8192;
        fseek($handle, 0, \SEEK_END);
        $offset = (int) ftell($handle);
        $atStart = true;

        try {
            while ($offset > 0 && substr_count($buffer, "\n") <= $maxLines) {
                $read = min($chunkSize, $offset);
                $offset -= $read;
                fseek($handle, $offset, \SEEK_SET);
                $buffer = (string) fread($handle, $read).$buffer;
            }
            $atStart = 0 === $offset;
        } finally {
            fclose($handle);
        }

        $lines = preg_split('/\r\n|\r|\n/', $buffer) ?: [];
        $lines = array_values(array_filter(array_map('rtrim', $lines), static fn (string $l): bool => '' !== $l));

        $truncated = !$atStart || \count($lines) > $maxLines;

        return [\count($lines) > $maxLines ? \array_slice($lines, -$maxLines) : $lines, $truncated];
    }

    /**
     * @return array{date: string, message: string, package: string}|null
     */
    private function parseLine(string $line): ?array
    {
        if (1 !== preg_match(self::LINE_PATTERN, $line, $m) || 'deprecation' !== $m['channel']) {
            return null;
        }

        $message = $this->stripContext($m['message']);
        $package = 1 === preg_match(self::PACKAGE_PATTERN, $message, $p) ? $p['package'] : 'autre';

        return ['date' => $m['date'], 'message' => $message, 'package' => $package];
    }

    private function stripContext(string $message): string
    {
        $clean = preg_replace('/\s+(\{.*\}|\[\])\s+(\{.*\}|\[\])\s*$/', '', $message);

        return trim($clean ?? $message);
    }
}
