<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\MailScenarioSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AsCommand(name: 'app:mail:dev-send', description: 'Envoie un ou tous les scenarios de mail de demo via le MAILER_DSN configure.')]
final class MailDevSendCommand extends Command
{
    public function __construct(
        private readonly MailScenarioSender $sender,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('recipient', InputArgument::REQUIRED, 'Adresse destinataire')
            ->addOption('scenario', 's', InputOption::VALUE_REQUIRED, 'Identifiant scenario, ou "all" pour tous', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recipient = (string) $input->getArgument('recipient');
        $scenario = (string) $input->getOption('scenario');

        $request = Request::create('https://up-animations.local/');
        $request->setLocale('fr');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack->push($request);

        if ('all' === $scenario) {
            $results = $this->sender->sendAll($recipient);
            $rows = [];
            $failures = 0;
            foreach ($results as $row) {
                $rows[] = [
                    $row['id'],
                    $row['success'] ? 'OK' : 'FAIL',
                    $row['error'] ?? '',
                ];
                if (!$row['success']) {
                    ++$failures;
                }
            }
            $io->table(['Scenario', 'Statut', 'Erreur'], $rows);

            if ($failures > 0) {
                $io->warning(sprintf('%d echec(s) sur %d.', $failures, count($results)));
                return Command::FAILURE;
            }
            $io->success(sprintf('%d mails envoyes a %s.', count($results), $recipient));
            return Command::SUCCESS;
        }

        $result = $this->sender->send($scenario, $recipient);
        if ($result['success']) {
            $io->success(sprintf('Scenario "%s" envoye a %s.', $scenario, $recipient));
            return Command::SUCCESS;
        }

        $io->error(sprintf('Echec scenario "%s": %s', $scenario, $result['error'] ?? 'unknown'));
        return Command::FAILURE;
    }
}
