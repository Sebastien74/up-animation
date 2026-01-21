<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Delete\ContactDeleteService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * ContactDeleteCommand.
 *
 * To remove contact by days limit
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'contacts:remove')]
class ContactDeleteCommand extends Command
{
    /**
     * ContactDeleteCommand constructor.
     */
    public function __construct(private readonly ContactDeleteService $contactDeleteService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To remove contact by days limit.')
            ->addArgument('limit', InputArgument::OPTIONAL, 'Days limit');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = $input->getArgument('limit') ? $input->getArgument('limit') : 30;
        $this->contactDeleteService->removeOld($limit);

        $message = 'Contacts successfully deleted!';
        $io->block($message, 'OK', 'fg=black;bg=green', ' ', true);

        return Command::SUCCESS;
    }
}
