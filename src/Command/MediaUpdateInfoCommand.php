<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Media\Media;
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

        $medias = $this->mediaRepository->findAll();
        $io->progressStart(count($medias));

        $updatedCount = 0;
        foreach ($medias as $media) {
            $filename = $media->getOriginalName();
            if (!$filename) {
                $io->progressAdvance();
                continue;
            }

            // VichUploader often uses subdirectories based on entity or website
            // Since we don't know the exact logic of DirectoryNamer here, 
            // we search for the file in the upload directory.
            $filePath = $this->findFile($uploadDir, $filename);

            if ($filePath && file_exists($filePath)) {
                $needsUpdate = false;

                if (!$media->getSize()) {
                    $media->setSize(filesize($filePath));
                    $needsUpdate = true;
                }

                if (!$media->getMimeType()) {
                    $media->setMimeType(mime_content_type($filePath) ?: null);
                    $needsUpdate = true;
                }

                if (!$media->getOriginalName()) {
                    $media->setOriginalName($filename);
                    $needsUpdate = true;
                }

                if (empty($media->getDimensions())) {
                    $sizes = @getimagesize($filePath);
                    if ($sizes) {
                        $media->setDimensions([$sizes[0], $sizes[1]]);
                        $needsUpdate = true;
                    } else {
                        $media->setDimensions([]);
                        $needsUpdate = true;
                    }
                }

                if ($needsUpdate) {
                    $updatedCount++;
                }
            }

            $io->progressAdvance();
            
            if ($updatedCount % 50 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $io->progressFinish();
        $io->success(sprintf('Updated %d medias.', $updatedCount));

        return Command::SUCCESS;
    }

    private function findFile(string $dir, string $filename): ?string
    {
        $it = new \RecursiveDirectoryIterator($dir);
        foreach (new \RecursiveIteratorIterator($it) as $file) {
            if ($file->getFilename() === $filename) {
                return $file->getPathname();
            }
        }
        return null;
    }
}
