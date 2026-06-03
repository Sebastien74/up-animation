<?php

declare(strict_types=1);

namespace App\Service\Development;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * DeprecationScanService.
 *
 * Runs a complete, static deprecation scan over the project's own PHP code with
 * PHPStan (phpstan-deprecation-rules), in browser-driven batches so the UI can
 * show a real progress bar. Each finding is tagged with the area it lives in
 * (src, migration, config, public...). Twig templates and vendor code are not
 * statically analysable here; those are surfaced by the runtime journal instead.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class DeprecationScanService
{
    private const int TIMEOUT_SECONDS = 240;
    private const int MAX_BATCH = 600;
    private const array SCAN_ROOTS = ['src', 'migrations', 'config', 'public'];
    private const string PACKAGE_PATTERN = '/Since (?<package>[\w\/.\-]+) [\d.]+:/';

    public function __construct(
        private string $projectDir,
        private PhpCliBinaryResolver $phpBinary,
    ) {
    }

    public function fileCount(): int
    {
        return \count($this->sourceFiles());
    }

    /**
     * Scan a slice of source files and return the deprecation findings it holds.
     *
     * @return array{
     *     total: int,
     *     processed: int,
     *     done: bool,
     *     findings: list<array{file: string, line: int, area: string, package: string, message: string}>,
     *     diag: array{exitCode: ?int, stderr: string, stdout: string}|null
     * }
     */
    public function scanBatch(int $offset, int $size): array
    {
        $files = $this->sourceFiles();
        $total = \count($files);
        $offset = max(0, $offset);
        $size = max(1, min($size, self::MAX_BATCH));
        $slice = \array_slice($files, $offset, $size);

        if (0 === $offset) {
            $this->resetStore();
        }

        if ([] === $slice) {
            return ['total' => $total, 'processed' => $total, 'done' => true, 'findings' => [], 'diag' => null];
        }

        $result = $this->analyse($slice);
        $this->appendStore($result['findings']);
        $processed = min($offset + \count($slice), $total);

        return [
            'total' => $total,
            'processed' => $processed,
            'done' => $processed >= $total,
            'findings' => $result['findings'],
            'diag' => $result['diag'],
        ];
    }

    /**
     * @return list<string> absolute paths, deterministic ordering across calls
     */
    private function sourceFiles(): array
    {
        $roots = array_values(array_filter(
            self::SCAN_ROOTS,
            fn (string $root): bool => is_dir($this->projectDir.\DIRECTORY_SEPARATOR.$root),
        ));

        $finder = (new Finder())
            ->files()
            ->in(array_map(fn (string $root): string => $this->projectDir.\DIRECTORY_SEPARATOR.$root, $roots))
            ->name('*.php')
            ->sortByName();

        $paths = [];
        foreach ($finder as $file) {
            $paths[] = $file->getRealPath();
        }

        return $paths;
    }

    /**
     * @param list<string> $files
     *
     * @return array{
     *     findings: list<array{file: string, line: int, area: string, package: string, message: string}>,
     *     diag: array{exitCode: ?int, stderr: string, stdout: string}|null
     * }
     */
    private function analyse(array $files): array
    {
        // Pass the file list through a temp neon config, not CLI args: a few hundred
        // absolute paths would blow past the Windows command-line length limit.
        $configPath = $this->projectDir.'/var/cache/phpstan-scan-batch.neon';
        @mkdir(\dirname($configPath), 0777, true);
        file_put_contents($configPath, $this->buildBatchConfig($files));

        $process = new Process(
            [
                $this->phpBinary->resolve(),
                'vendor/phpstan/phpstan/phpstan',
                'analyse',
                '--error-format=json',
                '--no-progress',
                '--no-interaction',
                '--memory-limit=1G',
                '-c',
                $configPath,
            ],
            $this->projectDir,
            null,
            null,
            self::TIMEOUT_SECONDS,
        );
        $process->run();
        @unlink($configPath);

        $json = json_decode($process->getOutput(), true);
        if (!\is_array($json) || !isset($json['files']) || !\is_array($json['files'])) {
            return [
                'findings' => [],
                'diag' => [
                    'exitCode' => $process->getExitCode(),
                    'stderr' => mb_substr(trim($process->getErrorOutput()), 0, 800),
                    'stdout' => mb_substr(trim($process->getOutput()), 0, 300),
                ],
            ];
        }

        $findings = [];
        foreach ($json['files'] as $path => $data) {
            $relative = $this->relativePath((string) $path);
            $area = $this->areaFromPath($relative);
            foreach (($data['messages'] ?? []) as $message) {
                if (!$this->isDeprecation($message)) {
                    continue;
                }
                $text = (string) ($message['message'] ?? '');
                $findings[] = [
                    'file' => $relative,
                    'line' => (int) ($message['line'] ?? 0),
                    'area' => $area,
                    'package' => $this->packageFromMessage($text),
                    'message' => $text,
                ];
            }
        }

        return ['findings' => $findings, 'diag' => null];
    }

    /**
     * @param list<string> $files
     */
    private function buildBatchConfig(array $files): string
    {
        $tmpDir = str_replace('\\', '/', $this->projectDir).'/var/cache/phpstan';
        $lines = [
            'parameters:',
            '    level: 0',
            '    tmpDir: '.$tmpDir,
            '    reportUnmatchedIgnoredErrors: false',
            '    paths:',
        ];
        foreach ($files as $file) {
            $lines[] = '        - '.str_replace('\\', '/', $file);
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param array<string, mixed> $message
     */
    private function isDeprecation(array $message): bool
    {
        if (str_contains((string) ($message['identifier'] ?? ''), 'deprecated')) {
            return true;
        }

        return false !== stripos((string) ($message['message'] ?? ''), 'deprecated');
    }

    private function relativePath(string $absolute): string
    {
        $normalized = str_replace('\\', '/', $absolute);
        $base = str_replace('\\', '/', $this->projectDir).'/';

        return str_starts_with($normalized, $base) ? substr($normalized, \strlen($base)) : $normalized;
    }

    private function areaFromPath(string $relative): string
    {
        return match (true) {
            str_starts_with($relative, 'src/') => 'src',
            str_starts_with($relative, 'migrations/') => 'migration',
            str_starts_with($relative, 'config/') => 'config',
            str_starts_with($relative, 'public/') => 'public',
            str_starts_with($relative, 'templates/') => 'template',
            str_starts_with($relative, 'vendor/') => 'vendor',
            default => 'autre',
        };
    }

    private function packageFromMessage(string $message): string
    {
        return 1 === preg_match(self::PACKAGE_PATTERN, $message, $matches) ? $matches['package'] : 'autre';
    }

    /**
     * Last persisted scan results, so the page keeps a trace across visits.
     *
     * @return array{
     *     available: bool,
     *     scannedAt: ?string,
     *     total: int,
     *     unique: int,
     *     byArea: list<array{name: string, count: int}>,
     *     byPackage: list<array{name: string, count: int}>,
     *     findings: list<array{file: string, line: int, area: string, package: string, message: string}>
     * }
     */
    public function lastResults(): array
    {
        $store = $this->readStore();
        $findings = $store['findings'] ?? [];

        $byArea = [];
        $byPackage = [];
        foreach ($findings as $finding) {
            $byArea[$finding['area']] = ($byArea[$finding['area']] ?? 0) + 1;
            $byPackage[$finding['package']] = ($byPackage[$finding['package']] ?? 0) + 1;
        }
        arsort($byArea);
        arsort($byPackage);

        return [
            'available' => null !== $store,
            'scannedAt' => $store['scannedAt'] ?? null,
            'total' => \count($findings),
            'unique' => \count(array_unique(array_column($findings, 'message'))),
            'byArea' => $this->toPairs($byArea),
            'byPackage' => $this->toPairs($byPackage),
            'findings' => $findings,
        ];
    }

    public function clearScan(): bool
    {
        $path = $this->storePath();

        return !is_file($path) || @unlink($path);
    }

    /**
     * @param array<string, int> $map
     *
     * @return list<array{name: string, count: int}>
     */
    private function toPairs(array $map): array
    {
        $pairs = [];
        foreach ($map as $name => $count) {
            $pairs[] = ['name' => $name, 'count' => $count];
        }

        return $pairs;
    }

    private function storePath(): string
    {
        return $this->projectDir.'/var/cache/deprecation-scan.json';
    }

    private function resetStore(): void
    {
        @mkdir(\dirname($this->storePath()), 0777, true);
        file_put_contents($this->storePath(), (string) json_encode(['scannedAt' => $this->now(), 'findings' => []]));
    }

    /**
     * @param list<array{file: string, line: int, area: string, package: string, message: string}> $findings
     */
    private function appendStore(array $findings): void
    {
        $store = $this->readStore() ?? ['scannedAt' => $this->now(), 'findings' => []];
        $store['findings'] = array_merge($store['findings'] ?? [], $findings);
        file_put_contents($this->storePath(), (string) json_encode($store));
    }

    /**
     * @return array{scannedAt?: string, findings?: list<array<string, mixed>>}|null
     */
    private function readStore(): ?array
    {
        $path = $this->storePath();
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return \is_array($data) ? $data : null;
    }

    private function now(): string
    {
        return (new \DateTime())->format(\DateTimeInterface::ATOM);
    }
}
