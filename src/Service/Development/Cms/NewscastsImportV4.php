<?php

declare(strict_types=1);

namespace App\Service\Development\Cms;

use App\Entity\Core\Website;
use App\Entity\Module\Newscast\Category;
use App\Entity\Module\Newscast\Newscast;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * NewscastsImportV4.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewscastsImportV4 extends ImportV4Service implements NewscastsImportV4Interface
{
    /**
     * To execute service.
     *
     * @throws \Exception
     */
    public function entities(Website $website): array
    {
        $this->setCore($website, Newscast::class, 'mod_news', 'id', 'DESC');
        $this->sqlService->setConnection('direct');
        return $this->entities;
    }

    /**
     * To execute service.
     *
     * @throws \Exception
     */
    public function execute(Website $website, int $importId): void
    {
        try {
            $this->setFolderName('Actualités', 'news');
            $this->setCore($website, Newscast::class, 'mod_news', 'news_date_end', 'DESC');
            $this->sqlService->setConnection('direct');
            $prefix = 'news';
            $repository = $this->coreLocator->em()->getRepository(Newscast::class);
            $category = $this->coreLocator->em()->getRepository(Category::class)->findOneBy(['website' => $website, 'slug' => 'principale']);
            $entityToImport = $this->sqlService->find('mod_news', 'id', $importId);
            $existing = $repository->findOneBy(['oldId' => $entityToImport['id'], 'website' => $website]);
            $locale = $this->getLocale($website, $entityToImport);
            $entity = $existing ?: new Newscast();
            $this->setProperties($entity, $website, $prefix, $entityToImport, $locale, $this->position);
            $publicationDate = !empty($entityToImport[$prefix.'_date_actu']) ? $entityToImport[$prefix.'_date_actu'] : null;
            $entity->setCategory($category);
            if ($publicationDate) {
                $entity->setPublicationStart(new \DateTime($publicationDate));
            }
            if (!empty($entityToImport['gallery_id'])) {
                $galleryToImport = $this->sqlService->find('fxc_medias_galleries', 'id', $entityToImport['gallery_id']);
                $this->addMediaRelations($entity, $website, $locale, 'gallery', $galleryToImport, false);
            }
            $this->coreLocator->em()->persist($entity);
            $this->coreLocator->em()->flush();
        } catch (\Exception $exception) {
            $entityToImport = $this->sqlService->find('mod_news', 'id', $importId);
            $logger = new Logger('form.helper');
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/import-newscasts.log', 10, Level::Critical));
            $logger->critical($exception->getMessage().' : '.$entityToImport['news_name']);
            return;
        }
    }
}