<?php

declare(strict_types=1);

namespace App\Service\Development\Cms;

use App\Entity\Core\Website;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * EntityImportV6.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EntityImportV6 extends ImportV6Service implements EntityImportV6Interface
{
    /**
     * To execute service.
     *
     * @throws \Exception
     */
    public function entities(Website $website): array
    {
        $this->sqlService->setConnection('direct');
        $prefix = $this->sqlService->prefix();
        $classname = urldecode($this->coreLocator->request()->get('classname'));
        $tables = json_decode(urldecode($this->coreLocator->request()->get('tables')));
        $this->setCore($website, $classname, $prefix.'_'.$tables[0], 'position');
        if (!$this->entities && !empty($tables[1])) {
            $this->setCore($website, $classname, $prefix.'_'.$tables[1], 'position');
        }

        return $this->entities;
    }

    /**
     * To execute service.
     *
     * @throws \Exception
     */
    public function execute(Website $website, int $importId): mixed
    {
        $entity = null;
        $tables = json_decode(urldecode($this->coreLocator->request()->get('tables')));
        try {
            $this->sqlService->setConnection('direct');
            $classname = urldecode($this->coreLocator->request()->get('classname'));
            $prefix = $this->sqlService->prefix();
            $entityToImport = $this->sqlService->find($prefix.'_'.$tables[0], 'id', $importId);
            $entityToImport = !$entityToImport && !empty($tables[1]) ? $this->sqlService->find($prefix.'_'.$tables[1], 'id', $importId) : $entityToImport;
            $repository = $this->coreLocator->em()->getRepository($classname);
            $existing = $repository->findOneBy(['slug' => $entityToImport['slug'], 'website' => $website]);
            $entity = $existing ?: new $classname();
            if (!empty($entityToImport['parent_id'])) {
                $parent = $this->execute($website, $entityToImport['parent_id']);
                $entity->setParent($parent);
            }
            $this->setProperties($entity, $website, $entityToImport, null, $tables[0]);
            $this->coreLocator->em()->persist($entity);
            $this->coreLocator->em()->flush();
        } catch (\Exception $exception) {
            $prefix = $this->sqlService->prefix();
            $entityToImport = $this->sqlService->find($prefix.'_'.$tables[0], 'id', $importId);
            $name = !empty($entityToImport['adminName']) ? $entityToImport['adminName'] : 'N/C';
            $logger = new Logger('form.helper');
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/import-entities.log', 10, Level::Critical));
            $logger->critical($exception->getMessage().' : '.$name);
        }

        return $entity;
    }
}
