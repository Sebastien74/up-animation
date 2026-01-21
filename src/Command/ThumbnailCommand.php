<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Website;
use App\Service\Core\ThumbnailGeneratorService;
use App\Service\Development\CommandHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * ThumbnailCommand.
 *
 * To generate thumbnails
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:thumbs:generate')]
class ThumbnailCommand extends Command
{
    /**
     * ThumbnailCommand constructor.
     */
    public function __construct(
        private readonly ThumbnailGeneratorService $generatorService,
        private readonly CommandHelper $commandHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To regenerate thumbnails.')
            ->addArgument('website', InputArgument::OPTIONAL, 'WebsiteModel entity.')
            ->addArgument('symfony_style', InputArgument::OPTIONAL, 'SymfonyStyle entity.')
            ->addArgument('output', InputArgument::OPTIONAL, 'Command output.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->commandHelper->getIo($input, $output);
        $output = $this->commandHelper->getOutput($input, $output);
        $website = $this->commandHelper->getWebsite($input, $io);

        if ($website instanceof Website) {
            $this->generate($website, $io, $output);
            $io->newLine();
            $io->block('Thumbnails successfully generated.', 'OK', 'fg=black;bg=green', ' ', true);
        }

        return Command::SUCCESS;
    }

    /**
     * Generate thumbnails.
     */
    private function generate(Website $website, SymfonyStyle $io, OutputInterface $output): void
    {
        $list = $this->generatorService->list($website);

        $count = 0;
        foreach ($list['files'] as $file) {
            ++$count;
            foreach ($list['thumbs'] as $thumb) {
                ++$count;
            }
        }

        $io->progressStart($count);
        foreach ($list['files'] as $file) {
            foreach ($list['thumbs'] as $thumb) {
                $this->generatorService->resolve($website, $thumb, urlencode($file['dirname']));
                $io->progressAdvance();
            }
            $this->generatorService->filters(urlencode($file['dirname']));
            $io->progressAdvance();
        }
        $io->progressFinish();
    }
}
