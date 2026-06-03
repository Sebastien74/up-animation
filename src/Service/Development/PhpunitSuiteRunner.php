<?php

declare(strict_types=1);

namespace App\Service\Development;

use Symfony\Component\Process\Process;

/**
 * PhpunitSuiteRunner.
 *
 * Runs a single PHPUnit testsuite in a child process and returns a structured
 * result parsed from the JUnit XML report. Used by the back-office dev tools to
 * trigger a regression run without ever exposing a shell.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PhpunitSuiteRunner
{
    private const int TIMEOUT_SECONDS = 120;

    public function __construct(
        private readonly string $projectDir,
        private readonly PhpCliBinaryResolver $phpBinary,
    ) {
    }

    public function run(string $testsuite): PhpunitRunResult
    {
        $junitPath = $this->projectDir.'/var/log/phpunit-'.$testsuite.'.xml';
        @mkdir(dirname($junitPath), 0777, true);
        @unlink($junitPath);

        $process = new Process(
            [
                $this->phpBinary->resolve(),
                'bin/phpunit',
                '--testsuite='.$testsuite,
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

        $cases = $this->parseJunit($junitPath);
        $totals = $this->aggregate($cases);
        $success = $totals['total'] > 0 && 0 === $totals['failed'] && 0 === $totals['errored'];

        return new PhpunitRunResult(
            testsuite: $testsuite,
            success: $success,
            exitCode: $process->getExitCode() ?? -1,
            durationSeconds: $duration,
            cases: $cases,
            totals: $totals,
            stdout: $process->getOutput(),
            stderr: $process->getErrorOutput(),
        );
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
