<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * ContentsImporterInterface.
 *
 * A bucket importer used by ImportContentsCommand to import one bucket of contents.json
 * into the database (products, categories, indexes, pages...).
 *
 * Each importer must:
 * - declare if it supports a bucket via supports()
 * - import payload and advance the shared ProgressBar for each processed item
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface ContentsImporterInterface
{
    /**
     * Whether this importer supports the given bucket name.
     */
    public function supports(string $bucket): bool;

    /**
     * Import a bucket payload.
     *
     * @param array<string, mixed> $bucketPayload
     */
    public function import(string $bucket, array $bucketPayload, SymfonyStyle $io, ProgressBar $progressBar, bool $dryRun = false): void;
}
