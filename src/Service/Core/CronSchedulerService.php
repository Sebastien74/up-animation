<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Command\CronCommand;
use App\Entity\Core\Domain;
use App\Entity\Core\ScheduledCommand;
use App\Entity\Core\Website;
use Cron\CronExpression;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * CronSchedulerService.
 *
 * Run all commands scheduled
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CronSchedulerService
{
    /**
     * Seconds after which a locked command is considered orphaned and its lock released.
     */
    private const LOCK_TIMEOUT = 3600;

    private string $logPath;
    private Logger $logger;

    /**
     * CronCommand constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly KernelInterface $kernel,
        private readonly MailerInterface $mailer,
    ) {
        $this->logPath = $kernel->getLogDir();
        if ($this->logPath) {
            $this->logPath = rtrim($this->logPath, '/\\').DIRECTORY_SEPARATOR;
            $this->logPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->logPath);
        }
        $this->logger = new Logger('CRON');
        $this->logger->pushHandler(new RotatingFileHandler($this->logPath.'cron-scheduler.log', 20, Level::Info));
    }

    /**
     * Execute.
     *
     * @throws \Exception
     */
    public function execute(?InputInterface $input = null, ?OutputInterface $output = null): void
    {
        $this->logger->info('[START] '.CronCommand::class);
        $this->logger->info('[START] '.CronSchedulerService::class);

        $this->releaseStaleLocks();

        $noneExecution = true;
        $commands = $this->entityManager->getRepository(ScheduledCommand::class)->findAll();

        foreach ($commands as $command) {
            /* @var ScheduledCommand $command */

            $this->entityManager->refresh($command);

            $logFilename = $this->getFilename($command);
            $commandLogger = new Logger('CRON');
            $commandLogger->pushHandler(new RotatingFileHandler($this->logPath.$logFilename, 10, Level::Info));

            if (!$this->isExecutable($command, $commandLogger)) {
                continue;
            }

            if ($command->isExecuteImmediately()) {
                $noneExecution = false;
                $this->executeCommand($command, $commandLogger, $logFilename);
            } else {
                try {
                    $cron = CronExpression::factory($command->getCronExpression());
                    $nextRunDate = $cron->getNextRunDate($command->getLastExecution());

                    if ($nextRunDate < new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'))) {
                        $noneExecution = false;
                        $this->executeCommand($command, $commandLogger, $logFilename);
                    }
                } catch (\Exception $exception) {
                    $this->logger->critical('[ERROR] - '.$command->getCommand().' ('.$exception->getMessage().')');
                    $commandLogger->critical('[ERROR] - '.$command->getCommand().' ('.$exception->getMessage().')');
                    continue;
                }
            }
        }

        if (true === $noneExecution) {
            $this->logger->info('[CLOSE] Nothing to do.');
        }

        $this->logger->info('[CLOSE] '.CronCommand::class.' executed.');
    }

    /**
     * Release orphaned locks left by commands that crashed mid-run without resetting locked=false.
     */
    private function releaseStaleLocks(): void
    {
        $staleCommands = $this->entityManager->getRepository(ScheduledCommand::class)
            ->findStaleLockedCommands(self::LOCK_TIMEOUT);

        if ([] === $staleCommands) {
            return;
        }

        foreach ($staleCommands as $command) {
            $command->setLocked(false);
            $this->logger->warning('[UNLOCK] '.$command->getCommand().' stale lock released (older than '.self::LOCK_TIMEOUT.'s)');
        }

        $this->entityManager->flush();
    }

    /**
     * Logger.
     */
    public function logger(string $message, ?InputInterface $input = null, bool $success = true): void
    {
        $logger = new Logger('CRON');

        if ($input) {
            $cronLogger = $input->getArgument('cronLogger');
            if ($cronLogger) {
                $logger->pushHandler(new RotatingFileHandler($this->logPath.$cronLogger, 10, Level::Info));
                $logger->info($message);
            }
            $commandLogger = $input->getArgument('commandLogger');
            if ($commandLogger) {
                $logger->pushHandler(new RotatingFileHandler($this->logPath.$commandLogger, 10, Level::Info));
                $logger->info($message);
            }
        } elseif ($message) {
            $logger->pushHandler(new RotatingFileHandler($this->logPath.'commands-executed.log', 10, Level::Info));
            if ($success) {
                $logger->info($message);
            } else {
                $logger->critical($message);
            }
        }
    }

    /**
     * Get log filename.
     */
    private function getFilename(ScheduledCommand $command): string
    {
        $filename = !preg_match('/.log/', $command->getLogFile()) ? $command->getLogFile().'.log' : $command->getLogFile();

        if (!$filename || '.log' === $filename) {
            $filename = 'cron-'.Urlizer::urlize($command->getCommand());
        }

        return $filename.'.log';
    }

    /**
     * Check if command is executable.
     */
    private function isExecutable(ScheduledCommand $command, Logger $commandLogger): bool
    {
        if (!$command->isActive() || $command->isLocked()) {
            $cmdMessage = $command->isLocked() ? '[LOCKED] '.$command->getCommand().' is locked' : '[DISABLED] '.$command->getCommand().' is disabled';
            $this->logger->warning($cmdMessage);
            $commandLogger->warning($cmdMessage);
            $website = $command->getWebsite();
            if ($command->isLocked() && $website instanceof Website) {
                $defaultDomain = $this->entityManager->getRepository(Domain::class)
                    ->findOneBy(['configuration' => $website->getConfiguration(), 'asDefault' => true]);
                $domainName = $defaultDomain?->getName();
                if ($domainName) {
                    $emails = ['dev@up-animations.fr'];
                    $message = 'CRON '.$command->getAdminName().' FAILED';
                    foreach ($emails as $email) {
                        // A mailer/transport misconfiguration must never abort the scheduler run.
                        try {
                            $notification = (new NotificationEmail())->from('dev@up-animations.fr')
                                ->to($email)
                                ->subject('CRON ERROR')
                                ->markdown("<p>An error has occurred on website <a href='".$domainName."'>".$domainName.'</a></p><br><p><small>'.$message.'</small></p>')
                                ->action('Aller sur le site', $domainName)
                                ->importance(NotificationEmail::IMPORTANCE_URGENT);
                            $this->mailer->send($notification);
                        } catch (\Throwable $exception) {
                            $this->logger->error('[ALERT] notification failed for '.$command->getCommand().' ('.$exception->getMessage().')');
                            $commandLogger->error('[ALERT] notification failed ('.$exception->getMessage().')');
                        }
                    }
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Execute command.
     *
     * @throws ConnectionException
     * @throws \Exception
     */
    private function executeCommand(ScheduledCommand $scheduledCommand, Logger $commandLogger, string $logFilename): void
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $notLockedCommand = $this->entityManager->getRepository(ScheduledCommand::class)->getNotLockedCommand($scheduledCommand);
            if (null === $notLockedCommand) {
                throw new \RuntimeException(sprintf('ScheduledCommand "%s" is already locked or unavailable.', (string) $scheduledCommand->getCommand()));
            }
            $scheduledCommand = $notLockedCommand;
            $scheduledCommand->setLastExecution(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $scheduledCommand->setLocked(true);
            $this->entityManager->persist($scheduledCommand);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            $this->logger->critical($exception->getMessage());

            return;
        }

        $this->logger->info('[START] '.$scheduledCommand->getCommand());
        $commandLogger->info('[START] '.$scheduledCommand->getCommand());

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => $scheduledCommand->getCommand(),
            'cronLogger' => 'cron-scheduler.log',
            'commandLogger' => $logFilename,
        ]);
        $output = new BufferedOutput();
        $returnCode = $application->run($input, $output);

        if (!$scheduledCommand->isExecuteImmediately()) {
            $scheduledCommand->setLastExecution(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        }

        $scheduledCommand->setLastReturnCode($returnCode);
        $scheduledCommand->setExecuteImmediately(false);
        $scheduledCommand->setLocked(false);
        $this->entityManager->persist($scheduledCommand);
        $this->entityManager->flush();
        $this->entityManager->refresh($scheduledCommand);

        $status = 0 === $returnCode ? '[SUCCESS]' : '[FAILED]';
        $this->logger->info($status.' '.$scheduledCommand->getCommand().' (code '.$returnCode.')');
        $commandLogger->info($status.' '.$scheduledCommand->getCommand().' (code '.$returnCode.')');

        $commandOutput = trim($output->fetch());
        if ('' !== $commandOutput) {
            $commandLogger->info($commandOutput);
        }
    }
}
