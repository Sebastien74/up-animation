<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Command\CacheCommand;
use App\Entity\Media\MediaRelationIntl;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Entity\Translation\Translation;
use App\Service\Core\XlsxFileReader;
use App\Service\Interface\CoreLocatorInterface;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Yaml;

/**
 * ImportService.
 *
 * Import translation by Xls files
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImportService
{
    private const bool SET_YAML = false;

    /**
     * ImportService constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly XlsxFileReader $fileReader,
        private readonly CacheCommand $cacheCommand,
    ) {
    }

    /**
     * Execute import.
     *
     * @throws Exception
     */
    public function execute(array $files): void
    {
        $namespaces = $this->getNamespaces();
        foreach ($files as $file) {
            $data = $this->fileReader->read($file);
            $iterations = $data->iterations['Worksheet'];
            $namespace = $this->getRepository($file, $namespaces);
            if (Translation::class === $namespace) {
                $this->addTranslations($iterations);
            } elseif ($namespace) {
                $this->addIntls($iterations, $namespace);
            }
        }
        $this->cacheCommand->clear();
    }

    /**
     * Get all namespaces.
     */
    private function getNamespaces(): array
    {
        $namespaces = [];
        $metadata = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $data) {
            if (0 === $data->getReflectionClass()->getModifiers()) {
                $namespace = $data->getName();
                $tableName = $this->coreLocator->em()->getClassMetadata($namespace)->getTableName();
                $namespaces[$tableName] = $data->getName();
            }
        }
        $namespaces['translations'] = Translation::class;

        return $namespaces;
    }

    /**
     * Get entity repository.
     */
    private function getRepository(UploadedFile $file, array $tables): mixed
    {
        $matches = explode('.', str_replace('.xlsx', '', $file->getClientOriginalName()));
        $matches = !empty($matches[0]) ? explode('-', $matches[0]) : null;
        $tableName = !empty($matches[0]) ? $matches[0] : null;

        return !empty($tables[$tableName]) ? $tables[$tableName] : null;
    }

    /**
     * Set Translation[].
     */
    private function addTranslations(array $data): void
    {
        $filesystem = new Filesystem();
        $repository = $this->coreLocator->em()->getRepository(Translation::class);
        foreach ($data as $translation) {
            if (!empty($translation['id'])) {
                $translationDb = $repository->find($translation['id']);
                if ($translationDb) {
                    $content = !empty($translation['translation']) ? $translation['translation'] : $translation['content'];
                    $translationDb->setContent($content);
                    $this->coreLocator->em()->persist($translationDb);
                    $this->coreLocator->em()->flush();
                    if (self::SET_YAML) {
                        $filePath = $this->coreLocator->projectDir().'/translations/'.$translation['domain'].'.'.$translation['locale'].'.yaml';
                        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
                        $values = [];
                        if ($filesystem->exists($filePath)) {
                            $values = Yaml::parseFile($filePath);
                        }
                        $values[$translation['content']] = $translation['translation'];
                        ksort($values);
                        $yaml = Yaml::dump($values);
                        file_put_contents($filePath, $yaml);
                    }
                }
            }
        }
    }

    /**
     * Set intl[].
     */
    private function addIntls(array $data, string $namespace): void
    {
        if (Url::class === $namespace) {
            $repositoryNamespace = Url::class;
        } elseif (Seo::class === $namespace) {
            $repositoryNamespace = Seo::class;
        }  elseif (str_contains(strtolower($namespace), 'mediarelation')) {
            $repositoryNamespace = MediaRelationIntl::class;
        } else {
            $referEntity = new $namespace();
            $intlData = method_exists($referEntity, 'getIntls')
                ? $this->coreLocator->metadata($referEntity, 'intls')
                : $this->coreLocator->metadata($referEntity, 'intl');
            $repositoryNamespace = $intlData->targetEntity;
        }

        $repository = $this->coreLocator->em()->getRepository($repositoryNamespace);
        $excludes = ['locale', 'website', 'id'];
        foreach ($data as $translation) {
            if (!empty($translation['id'])) {
                $intl = $repository->find($translation['id']);
                if ($intl) {
                    foreach ($translation as $property => $value) {
                        $setter = 'set'.ucfirst($property);
                        if ($intl && !in_array($property, $excludes) && !empty($value) && method_exists($intl, $setter)) {
                            $intl->$setter($value);
                        }
                    }
                    $this->coreLocator->em()->persist($intl);
                    $this->coreLocator->em()->flush();
                }
            }
        }
    }
}
