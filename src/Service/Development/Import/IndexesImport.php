<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * IndexesImport.
 *
 * Placeholder importer for the "indexes" bucket from contents.json.
 * We'll implement the real logic later.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AutoconfigureTag('app.contents_importer')]
class IndexesImport implements ContentsImporterInterface
{
    public function supports(string $bucket): bool
    {
        return $bucket === 'indexes';
    }

    /**
     * @param array<string, mixed> $bucketPayload
     */
    public function import(string $bucket, array $bucketPayload, SymfonyStyle $io, ProgressBar $progressBar, bool $dryRun = false): void
    {
        $count = count($bucketPayload);

        foreach (array_keys($bucketPayload) as $url) {
            $progressBar->setMessage(sprintf('Importing %s', $bucket));
            $progressBar->advance();
        }

        $io->writeln(sprintf('%s: %d item(s)%s', self::class, $count, $dryRun ? ' (dry-run)' : ''));
    }
}
