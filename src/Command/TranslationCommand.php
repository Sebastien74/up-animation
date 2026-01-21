<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Service\Development\CommandHelper;
use App\Service\Translation\Extractor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * TranslationCommand.
 *
 * To extract and generate translations
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:cmd:translations')]
class TranslationCommand extends Command
{
    /**
     * TranslationCommand constructor.
     */
    public function __construct(
        private readonly Extractor $extractor,
        private readonly CommandHelper $commandHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To regenerate translations.')
            ->addArgument('website', InputArgument::OPTIONAL, 'Website entity.')
            ->addArgument('symfony_style', InputArgument::OPTIONAL, 'SymfonyStyle entity.')
            ->addArgument('output', InputArgument::OPTIONAL, 'Command output.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->commandHelper->getIo($input, $output);

        if (!$this->commandHelper->isAllowed($io)) {
            return Command::FAILURE;
        }

        $output = $this->commandHelper->getOutput($input, $output);
        $website = $this->commandHelper->getWebsite($input, $io);

        if ($website instanceof Website) {
            try {
                $configuration = $website->getConfiguration();
                $this->extract($website, $configuration, $io, $output);
                $this->generate($configuration, $io, $output);
                $this->initFiles($configuration, $io, $output);
            } catch (\Exception $exception) {
                $io->block($exception->getMessage(), 'FAILED', 'fg=black;bg=red', ' ', true);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Extraction.
     *
     * @throws \Exception
     */
    private function extract(Website $website, Configuration $configuration, SymfonyStyle $io, OutputInterface $output): void
    {
        $defaultLocale = $configuration->getLocale();
        $locales = $configuration->getAllLocales();

        $output->writeln('<comment>Entities translations extraction progressing...</comment>');
        $this->extractor->extractEntities($website, $defaultLocale, $locales);
        $output->writeln('<info>Entities translations successfully extracted.</info>');
        $io->newLine();

        foreach ($locales as $locale) {
            $output->writeln('<comment>Project translations ['.strtoupper($locale).'] extraction progressing...</comment>');
            $this->extractor->extract($locale);
            $output->writeln('<info>Project translations ['.strtoupper($locale).'] successfully extracted.</info>');
            $io->newLine();
        }
    }

    /**
     * Generate translations.
     */
    private function generate(Configuration $configuration, SymfonyStyle $io, OutputInterface $output): void
    {
        $defaultLocale = $configuration->getLocale();
        $locales = $configuration->getAllLocales();

        $output->writeln('<comment>Yaml translations extraction progressing...</comment>');
        $yamlTranslations = $this->extractor->findYaml($locales);
        $output->writeln('<info>Yaml translations successfully extracted.</info>');
        $io->newLine();

        $count = 0;
        foreach ($yamlTranslations as $domain => $localeTranslations) {
            foreach ($localeTranslations as $locale => $translations) {
                $count = $count + count($translations);
            }
        }

        $output->writeln('<comment>Translations generation progressing...</comment>');
        $io->newLine();

        $io->progressStart($count);
        foreach ($yamlTranslations as $domain => $localeTranslations) {
            foreach ($localeTranslations as $locale => $translations) {
                foreach ($translations as $keyName => $content) {
                    $this->extractor->generateTranslation($defaultLocale, $locale, $domain, $content, strval($keyName));
                    $io->progressAdvance();
                }
            }
        }
        $io->progressFinish();
    }

    /**
     * Generate new yaml.
     */
    private function initFiles(Configuration $configuration, SymfonyStyle $io, OutputInterface $output): void
    {
        $output->writeln('<comment>Files regeneration progressing...</comment>');
        $this->extractor->initFiles($configuration->getAllLocales());
        $output->writeln('<info>Files successfully regenerated.</info>');
        $io->newLine();

        $output->writeln('<comment>Clearing cache...</comment>');
        $this->extractor->clearCache();

        $io->newLine();
        $io->block('Translations successfully generated.', 'OK', 'fg=black;bg=green', ' ', true);
    }
}
