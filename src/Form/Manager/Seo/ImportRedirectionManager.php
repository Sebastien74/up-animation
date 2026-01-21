<?php

declare(strict_types=1);

namespace App\Form\Manager\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\Redirection;
use App\Repository\Seo\RedirectionRepository;
use App\Service\Core\XlsxFileReader;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * ImportRedirectionManager.
 *
 * Manage SEO xls Redirection import
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ImportRedirectionManager::class, 'key' => 'seo_import_redirection_form_manager'],
])]
class ImportRedirectionManager
{
    private RedirectionRepository $repository;
    private array $mapping = [];
    private array $iterations = [];
    private bool $message = false;

    /**
     * ImportRedirectionManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly XlsxFileReader $fileReader,
        private readonly string $logDir,
        private readonly RedirectionManager $redirectionManager,
    ) {
        $this->repository = $this->entityManager->getRepository(Redirection::class);
    }

    /**
     * Execute import.
     *
     * @throws Exception
     */
    public function execute(FormInterface $form, Website $website): bool
    {
        $tmpFile = $form->getData()['xls_file'];
        $response = $this->fileReader->read($tmpFile, false, true);

        if (property_exists($response, 'iterations')) {
            $this->mapping = $response->mapping;
            $this->iterations = $response->iterations;
            if (!$this->isValidFile() || !$this->iterations) {
                return false;
            }
            $this->cleanIterations();
            $this->parse($website);
            $redirections = $this->entityManager->getRepository(Redirection::class)->findBy(['website' => $website]);
            $this->redirectionManager->setCache($redirections);
        }

        return true;
    }

    private function cleanIterations(): void
    {
        /* Delete empty rows */
        foreach ($this->iterations as $key => $row) {
            $values = [];
            foreach ($row as $rowKey => $value) {
                $values[strtolower($rowKey)] = $value;
            }
            $row = $this->iterations[$key] = $values;
            if (empty($row['locale']) && empty($row['old']) && empty($row['new'])) {
                unset($this->iterations[$key]);
            }
        }
    }

    /**
     * Parse data.
     */
    private function parse(Website $website): void
    {
        foreach ($this->iterations as $key => $row) {
            $existing = $this->getRedirection($website, $row['locale'], $row['old'], $row['new']);
            if (!$existing && $row['new'] && $row['old'] && $row['locale'] && '/' !== $row['old']) {
                $this->addRedirection($website, $row['locale'], $row['old'], $row['new']);
            } else {
                $this->logger($row['old'], strval($key), $row['new'], $row['locale']);
            }
        }

        if ($this->message) {
            $session = new Session();
            $session->getFlashBag()->add('error', "Une ou plusieurs redirections n'ont pas été générées. Consulter le fichier redirections.log.");
        }
    }

    /**
     * Get Redirection.
     */
    private function getRedirection(Website $website, string $locale, ?string $old = null, ?string $new = null): ?Redirection
    {
        return $this->repository->findOneBy([
            'website' => $website,
            'locale' => $locale,
            'old' => $old,
            'new' => $new,
        ]);
    }

    /**
     * Add Redirection.
     */
    private function addRedirection(Website $website, string $locale, ?string $old = null, ?string $new = null): void
    {
        $redirection = new Redirection();
        $redirection->setWebsite($website);
        $redirection->setOld($old);
        $redirection->setNew($new);
        $redirection->setLocale($locale);

        $this->entityManager->persist($redirection);
        $this->entityManager->flush();
    }

    /**
     * Check if is valid file.
     */
    private function isValidFile(): bool
    {
        $columns = ['locale', 'old', 'new'];
        $count = 0;
        foreach ($this->mapping as $name => $column) {
            if (in_array(strtolower($column), $columns)) {
                ++$count;
            }
        }
        if ($count != count($columns)) {
            $session = new Session();
            $session->getFlashBag()->add('error', "Les entêtes ont été retirées du fichier d'origine.");
            return false;
        }
        return true;
    }

    /**
     * Log errors.
     */
    private function logger(?string $old = null, ?string $key = null, ?string $new = null, ?string $locale = null): void
    {
        $this->message = true;

        $logger = new Logger('redirection');
        $logger->pushHandler(new RotatingFileHandler($this->logDir.'/redirection.log', 10, Level::Info));

        if ('/' !== $old) {
            $logger->info('Invalid URI '.$old);
        } elseif (!empty($exist)) {
            $logger->info('Redirection for old URI '.$old.' already exist');
        }

        if (empty($locale)) {
            $logger->info('Locale empty.');
        }

        $logger->alert('Redirection failed: XLSX row => '.$key.' locale => '.$locale.' old =>  '.$old.' - new => '.$new);
    }
}
