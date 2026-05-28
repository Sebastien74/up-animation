<?php

declare(strict_types=1);

namespace App\Service\Development;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class MailTestRunner
{
    private const string TESTSUITE = 'mail';
    private const int TIMEOUT_SECONDS = 120;

    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function run(): MailTestResult
    {
        $junitPath = $this->projectDir.'/var/log/phpunit-mail.xml';
        @mkdir(dirname($junitPath), 0777, true);
        @unlink($junitPath);

        $phpBinary = $this->resolvePhpBinary();

        $process = new Process(
            [
                $phpBinary,
                'bin/phpunit',
                '--testsuite='.self::TESTSUITE,
                '--testdox',
                '--colors=never',
                '--log-junit='.$junitPath,
            ],
            $this->projectDir,
            null,
            null,
            self::TIMEOUT_SECONDS,
        );

        $start = microtime(true);
        $process->run();
        $duration = microtime(true) - $start;

        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();
        $exitCode = $process->getExitCode() ?? -1;

        $cases = $this->parseJunit($junitPath);
        $stats = $this->aggregate($cases);

        $success = $stats['total'] > 0 && 0 === $stats['failed'] && 0 === $stats['errored'];

        return new MailTestResult(
            success: $success,
            exitCode: $exitCode,
            durationSeconds: $duration,
            cases: $cases,
            totals: $stats,
            stdout: $stdout,
            stderr: $stderr,
        );
    }

    /**
     * Resolve a usable PHP CLI binary.
     *
     * Under WAMP / Apache mod_php, PHP_BINARY points to httpd.exe and is unusable.
     * Strategy: explicit env override, then loaded php.ini directory, then PhpExecutableFinder, then PATH.
     */
    private function resolvePhpBinary(): string
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

    /**
     * @return list<array{class: string, name: string, status: string, time: float, message: ?string}>
     */
    private function parseJunit(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $xml = @simplexml_load_file($path);
        if (false === $xml) {
            return [];
        }

        $cases = [];
        foreach ($xml->xpath('//testcase') ?? [] as $node) {
            $status = 'passed';
            $message = null;

            if (isset($node->failure)) {
                $status = 'failed';
                $message = trim((string) $node->failure);
            } elseif (isset($node->error)) {
                $status = 'error';
                $message = trim((string) $node->error);
            } elseif (isset($node->skipped)) {
                $status = 'skipped';
            }

            $cases[] = [
                'class' => (string) $node['class'],
                'name' => (string) $node['name'],
                'status' => $status,
                'time' => (float) ($node['time'] ?? 0.0),
                'message' => $message,
            ];
        }

        return $cases;
    }

    /**
     * @param list<array{status: string}> $cases
     *
     * @return array{total: int, passed: int, failed: int, errored: int, skipped: int}
     */
    private function aggregate(array $cases): array
    {
        $totals = ['total' => 0, 'passed' => 0, 'failed' => 0, 'errored' => 0, 'skipped' => 0];
        foreach ($cases as $case) {
            ++$totals['total'];
            $bucket = match ($case['status']) {
                'failed' => 'failed',
                'error' => 'errored',
                'skipped' => 'skipped',
                default => 'passed',
            };
            ++$totals[$bucket];
        }

        return $totals;
    }
}
