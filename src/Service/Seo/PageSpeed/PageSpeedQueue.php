<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * PageSpeedQueue.
 *
 * Disk-backed queue of pending PageSpeed measurements. The admin enqueues a job
 * (instant, no Google call); the cron-run command drains it off the web worker.
 * One file per job, deduplicated by website/code/locale, so re-triggering a page
 * just overwrites its pending job.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PageSpeedQueue
{
    private readonly string $dir;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->dir = $projectDir.'/var/psi-queue';
    }

    /**
     * @param array{publicUrl: string, locale: ?string, websiteId: int, code: ?string} $job
     */
    public function enqueue(array $job): void
    {
        $this->ensureDir();
        $key = hash('crc32b', (string) $job['websiteId'].'|'.((string) ($job['code'] ?? '')).'|'.((string) ($job['locale'] ?? '')));
        file_put_contents($this->dir.'/'.$key.'.json', json_encode($job, JSON_THROW_ON_ERROR), LOCK_EX);
    }

    /**
     * @return array<string, array<string, mixed>> path => job
     */
    public function pending(int $limit = 1): array
    {
        $this->ensureDir();
        $files = glob($this->dir.'/*.json') ?: [];
        sort($files);
        $jobs = [];
        foreach (array_slice($files, 0, max(1, $limit)) as $file) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                $jobs[$file] = $decoded;
            } else {
                @unlink($file);
            }
        }

        return $jobs;
    }

    public function remove(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Acquire a non-blocking exclusive lock so overlapping cron ticks never
     * double-process. Returns the lock handle to keep open, or null if busy.
     *
     * @return resource|null
     */
    public function acquire()
    {
        $this->ensureDir();
        $handle = fopen($this->dir.'/.lock', 'c');
        if (false === $handle) {
            return null;
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        return $handle;
    }

    private function ensureDir(): void
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }
}
