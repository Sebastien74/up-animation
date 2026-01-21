<?php

declare(strict_types=1);

namespace App\Service\Development;

use App\Entity\Core\Website;
use App\Repository\Core\WebsiteRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * CommandHelper.
 *
 * Symfony style website selector for command
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CommandHelper
{
    private array $commands = [];

    /**
     * CommandHelper constructor.
     */
    public function __construct(
        private readonly WebsiteRepository $websiteRepository,
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * Check if user is allowed to execute command.
     */
    public function isAllowed(SymfonyStyle $io): bool
    {
        if ('local' !== $_ENV['APP_ENV']) {
            $io->error("You're not allowed to run this command!!!");

            return false;
        }

        return true;
    }

    /**
     * Set App command.
     */
    public function setCommands(): array
    {
        $this->commands['update'] = ['command' => [
            'command' => 'doctrine:schema:update',
            '--force' => true,
            '--complete' => true,
        ],
            'description' => 'Executes (or dumps) the SQL needed to update the database schema to match the current mapping metadata',
            'start' => 'Updating in progress...',
            'finish' => "You're entities are successfully updated.",
        ];

        $this->commands['entities'] = ['command' => [
            'command' => 'app:cmd:entities',
            'website' => true,
            'symfony_style' => true,
            'output' => true,
        ],
            'description' => 'To generate entities configurations',
        ];

        $this->commands['translations'] = ['command' => [
            'command' => 'app:cmd:translations',
            'website' => true,
            'symfony_style' => true,
            'output' => true,
        ],
            'description' => 'To generate translations',
        ];

        return $this->commands;
    }

    /**
     * Set command argument.
     */
    public function commandArgument(string $alias, string $argument, $value): void
    {
        $this->commands[$alias]['command'][$argument] = $value;
    }

    /**
     * Generate selector.
     */
    public function getWebsites(SymfonyStyle $io): ?Website
    {
        $websites = $this->websiteRepository->findAll();
        $websitesSlugs = [];

        foreach ($websites as $website) {
            $websitesSlugs[] = $website->getSlug();
        }

        if ($websitesSlugs) {
            $websiteCode = $io->choice('Choose Website code', $websitesSlugs);

            return $this->websiteRepository->findOneBy(['slug' => $websiteCode]);
        }

        $io->getErrorStyle()->warning('No Website found!');

        return null;
    }

    /**
     * Get Website.
     */
    public function getWebsite(InputInterface $input, SymfonyStyle $io): ?Website
    {
        $websiteArgument = $input->getArgument('website');
        $website = is_string($websiteArgument)
            ? $this->websiteRepository->findOneBy(['slug' => $websiteArgument])
            : (is_numeric($websiteArgument) ? $this->websiteRepository->find($websiteArgument) : null);
        if ($website instanceof Website) {
            return $website;
        }

        return $websiteArgument instanceof Website ? $websiteArgument : $this->getWebsites($io);
    }

    /**
     * Get SymfonyStyle.
     */
    public function getIo(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        return $input->getArgument('symfony_style')
            ? $input->getArgument('symfony_style') : new SymfonyStyle($input, $output);
    }

    /**
     * Get SymfonyStyle.
     */
    public function getOutput(InputInterface $input, OutputInterface $output): OutputInterface
    {
        return $input->getArgument('output') ? $input->getArgument('output') : $output;
    }

    /**
     * Run command.
     *
     * @throws \Exception
     */
    public function runCmd(string $alias, SymfonyStyle $io, OutputInterface $output): void
    {
        $command = $this->commands[$alias]['command'];

        if (!empty($this->commands[$alias]['start'])) {
            $output->writeln('<comment>'.$this->commands[$alias]['start'].'</comment>');
            $io->newLine();
        }

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        if (!empty($command['website']) && $command['website'] instanceof Website) {
            $command['website'] = $command['website']->getId();
        }

        $input = new ArrayInput($command);
        $output = new BufferedOutput();
        $application->run($input, $output);

        if (!empty($this->commands[$alias]['finish'])) {
            $io->block($this->commands[$alias]['finish'], 'OK', 'fg=black;bg=green', ' ', true);
        }
    }
}
