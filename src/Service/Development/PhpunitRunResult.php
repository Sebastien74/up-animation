<?php

declare(strict_types=1);

namespace App\Service\Development;

final readonly class PhpunitRunResult
{
    /**
     * @param list<array{class: string, name: string, status: string, time: float, message: ?string}> $cases
     * @param array{total: int, passed: int, failed: int, errored: int, skipped: int}                $totals
     */
    public function __construct(
        public string $testsuite,
        public bool $success,
        public int $exitCode,
        public float $durationSeconds,
        public array $cases,
        public array $totals,
        public string $stdout,
        public string $stderr,
    ) {
    }

    public function toArray(): array
    {
        return [
            'testsuite' => $this->testsuite,
            'success' => $this->success,
            'exitCode' => $this->exitCode,
            'duration' => round($this->durationSeconds, 3),
            'totals' => $this->totals,
            'cases' => $this->cases,
            'stdout' => $this->stdout,
            'stderr' => $this->stderr,
        ];
    }
}
