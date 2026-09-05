<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\Media\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:media:update-info', description: 'Update missing media info (size, mimeType, originalName, dimensions)')]
class MediaUpdateInfoCommand extends Command
{
    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $uploadDir = $projectDir . '/public/uploads';

        $paths = $this->indexFiles($uploadDir);
        $medias = $this->mediaRepository->findAll();
        $io->progressStart(count($medias));

        $updatedCount = 0;
        $processed = 0;
        foreach ($medias as $media) {
            $filename = $media->getOriginalName();
            $filePath = $filename ? ($paths[$filename] ?? null) : null;

            if ($filePath) {
                $needsUpdate = false;
                if (!$media->getSize()) {
                    $media->setSize(filesize($filePath) ?: null);
                    $needsUpdate = true;
                }
                if (!$media->getMimeType()) {
                    $media->setMimeType(mime_content_type($filePath) ?: null);
                    $needsUpdate = true;
                }
                if ($needsUpdate) {
                    ++$updatedCount;
                }
            }

            $io->progressAdvance();

            if (0 === ++$processed % 50) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $io->progressFinish();
        $io->success(sprintf('Updated %d medias.', $updatedCount));

        return Command::SUCCESS;
    }

    /**
     * @return array<string, string> original name => absolute path
     */
    private function indexFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $paths = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && !isset($paths[$file->getFilename()])) {
                $paths[$file->getFilename()] = $file->getPathname();
            }
        }

        return $paths;
    }
}
