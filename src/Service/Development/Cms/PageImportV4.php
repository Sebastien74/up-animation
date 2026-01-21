<?php

declare(strict_types=1);

namespace App\Service\Development\Cms;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * PageImportV4.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PageImportV4 extends ImportV4Service implements PageImportV4Interface
{
    /**
     * To execute service.
     *
     * @throws \Exception
     */
    public function entities(Website $website): array
    {
        $this->setCore($website, Page::class, 'fxc_pages');
        $this->sqlService->setConnection('direct');
        return $this->entities;
    }

    /**
     * To execute service.
     *
     * @throws \Exception
     */
    public function execute(Website $website, int $importId): ?Page
    {
        $entity = null;
        try {
            $this->setFolderName('Pages', 'pages');
            $this->setCore($website, Page::class, 'fxc_pages', 'position');
            $this->sqlService->setConnection('direct');
            $prefix = 'page';
            $entityToImport = $this->sqlService->find('fxc_pages', 'id', $importId);
            $excluded = ['accueil', 'mentions-legales', 'plan-de-site', 'news'];
            if (!in_array($entityToImport[$prefix.'_code'], $excluded)) {
                $repository = $this->coreLocator->em()->getRepository(Page::class);
                $existing = $repository->findOneBy(['slug' => $entityToImport[$prefix.'_code'], 'website' => $website]);
                $locale = $this->getLocale($website, $entityToImport);
                $entity = $existing ?: new Page();
                $this->setProperties($entity, $website, $prefix, $entityToImport, $locale, $this->position);
                if (!empty($entityToImport['parent_id'])) {
                    $page = $this->execute($website, $entityToImport['parent_id']);
                    $entity->setParent($page);
                }
                $this->coreLocator->em()->persist($entity);
                $this->coreLocator->em()->flush();
            }
        } catch (\Exception $exception) {
            $entityToImport = $this->sqlService->find('fxc_pages', 'id', $importId);
            $logger = new Logger('form.helper');
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/import-pages.log', 10, Level::Critical));
            $logger->critical($exception->getMessage().' : '.$entityToImport['page_name']);
        }
        return $entity;
    }
}