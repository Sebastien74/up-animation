<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Entity\Api;
use App\Entity\BaseMediaRelation;
use App\Entity\Core\ConfigurationMediaRelation;
use App\Entity\Core\Entity;
use App\Entity\Core\Website;
use App\Entity\Information\Information;
use App\Entity\Layout\Block;
use App\Entity\Layout\Page;
use App\Entity\Media\Media;
use App\Entity\Seo\Seo;
use App\Entity\Seo\SeoConfiguration;
use App\Entity\Seo\Url;
use App\Entity\Translation\Translation;
use App\Entity\Translation\TranslationDomain;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * ExportService.
 *
 * Generate ZipArchive of translations files
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ExportService
{
    private ?string $dirname;
    private Website $website;

    /**
     * ExportService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $dirname = $this->coreLocator->projectDir().'/bin/export';
        $this->dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
    }

    /**
     * Execute exportation.
     *
     * @throws Exception|MappingException|NonUniqueResultException|\PhpOffice\PhpSpreadsheet\Exception
     */
    public function execute(Website $website): void
    {
        $this->website = $website;

        $this->removeXlsxFiles();

        $defaultLocale = $website->getConfiguration()->getLocale();
        $locales = $website->getConfiguration()->getLocales();

        $intls = $this->getIntls();
//        $intls = $this->generateSeo($intls, $defaultLocale, $locales);
        $intls = $this->generateIntls($intls, $defaultLocale, $locales);
        $this->generateCsvIntls($intls, $defaultLocale);

        $translations = $this->getTranslations($defaultLocale);
        $this->generateCsvTranslations($translations, $defaultLocale);
    }

    /**
     * Generate ZipArchive.
     */
    public function zip(): bool|string
    {
        $finder = Finder::create();
        $finder->files()->in($this->dirname)->name('*.xlsx');

        $zip = new \ZipArchive();
        $zipName = 'translations.zip';
        $zip->open($zipName, \ZipArchive::CREATE);
        foreach ($finder as $file) {
            $zip->addFromString($file->getFilename(), $file->getContents());
        }
        $zip->close();

        return $finder->count() ? $zipName : false;
    }

    /**
     * Remove old Xlsx files.
     */
    private function removeXlsxFiles(): void
    {
        $filesystem = new Filesystem();
        $finder = Finder::create();
        $finder->files()->in($this->dirname)->name('*.xlsx');
        foreach ($finder as $file) {
            $filesystem->remove($file->getRealPath());
        }
    }

    /**
     * Get all intl.
     *
     * @throws NonUniqueResultException
     */
    private function getIntls(): array
    {
        $excluded = [
            BaseMediaRelation::class,
            Api\Facebook::class,
            Api\Instagram::class,
            Information::class,
            SeoConfiguration::class,
            ConfigurationMediaRelation::class,
        ];
        $metadata = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();
        $intls = [];

        foreach ($metadata as $data) {
            $namespace = $data->getName();
            if (0 === $data->getReflectionClass()->getModifiers() && !in_array($namespace, $excluded)) {
                $referEntity = new $namespace();
                $tableName = $this->coreLocator->em()->getClassMetadata($namespace)->getTableName();
                if (method_exists($referEntity, 'getIntls') || method_exists($referEntity, 'getIntl')) {
                    if (method_exists($referEntity, 'getWebsite')) {
                        $entities = $this->coreLocator->em()->getRepository($namespace)->createQueryBuilder('e')
                            ->andWhere('e.website = :website')
                            ->setParameter('website', $this->website)
                            ->getQuery()
                            ->getResult();
                    } else {
                        $entities = $this->coreLocator->em()->getRepository($namespace)->findAll();
                        foreach ($entities as $key => $entity) {
                            if (method_exists($entity, 'getMedia') && $entity->getMedia() && $entity->getMedia()->getWebsite()->getId() !== $this->website->getId()) {
                                unset($entities[$key]);
                            }
                        }
                    }
                    $isCollection = method_exists($referEntity, 'getIntls');
                    foreach ($entities as $entity) {
                        $export = true;
                        if ($entity instanceof Block) {
                            $layout = $entity->getCol()->getZone()->getLayout();
                            $layoutParent = $layout ? $this->coreLocator->em()->getRepository(Page::class)->findOneBy(['layout' => $layout]) : null;
                            if ($layoutParent instanceof Page) {
                                foreach ($layoutParent->getUrls() as $url) {
                                    if ($url->isArchived()) {
                                        $export = false;
                                        break;
                                    }
                                }
                            }
                        }
                        if ($export) {
                            if ($isCollection) {
                                foreach ($entity->getIntls() as $intl) {
                                    $intls[$tableName][$entity->getId()][$intl->getLocale()] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => true];
                                }
                            } else {
                                $intl = $entity->getIntl() ? $entity->getIntl() : $this->addIntl(false, $tableName, $entity, $entity->getLocale());
                                if ($intl) {
                                    $intls[$tableName][$entity->getId()][$intl->getLocale()] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => false];
                                }
                            }
                        }
                    }
                }
                if (!str_contains($namespace, 'MediaRelation') && method_exists($referEntity, 'getIntl')) {
                    $entities = method_exists($referEntity, 'getWebsite') ? $this->coreLocator->em()->getRepository($namespace)
                        ->createQueryBuilder('e')
                        ->andWhere('e.website = :website')
                        ->setParameter('website', $this->website)
                        ->getQuery()
                        ->getResult() : $this->coreLocator->em()->getRepository($namespace)->findAll();
                    foreach ($entities as $entity) {
                        if ($entity->getIntl()) {
                            $intl = $entity->getIntl() ? $entity->getIntl() : $this->addIntl(false, $tableName, $entity, $entity->getLocale());
                            $intls[$tableName][$entity->getId()][$intl->getLocale()] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => false];
                        }
                    }
                }
            }
        }

        return $intls;
    }

    /**
     * Get and generate all seo.
     *
     * @throws NonUniqueResultException
     */
    private function generateSeo(array $intls, string $defaultLocale, array $websiteLocales): array
    {
        $metadata = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();
        $namespaces = [];
        foreach ($metadata as $data) {
            $namespace = $data->getName();
            if (0 === $data->getReflectionClass()->getModifiers()) {
                $referEntity = new $namespace();
                if (method_exists($referEntity, 'getUrls')) {
                    $namespaces[] = $namespace;
                }
            }
        }

        $tableName = $this->coreLocator->em()->getClassMetadata(Seo::class)->getTableName();
        $entities = $this->coreLocator->em()->getRepository(Seo::class)->createQueryBuilder('e')
            ->leftJoin('e.url', 'u')
            ->andWhere('u.website = :website')
            ->andWhere('u.online = :online')
            ->andWhere('u.archived = :archived')
            ->setParameter('website', $this->website)
            ->setParameter('archived', false)
            ->setParameter('online', true)
            ->getQuery()
            ->getResult();

        $intls[$tableName] = [];
        foreach ($entities as $entity) {
            $intls[$tableName][$entity->getId()][$entity->getUrl()->getLocale()] = (object) ['entity' => $entity, 'intl' => $entity, 'isCollection' => false];
        }

        foreach ($intls[$tableName] as $locales) {

            $seoLocales = $urlLocales = [];
            $defaultSeo = !empty($locales[$defaultLocale]) ? $locales[$defaultLocale]->intl : null;
            $defaultUrl = $defaultSeo?->getUrl();

            /* Get master entity */
            $masterEntity = null;
            foreach ($namespaces as $namespace) {
                $parentEntity = $this->coreLocator->em()->getRepository($namespace)->createQueryBuilder('e')
                    ->leftJoin('e.urls', 'u')
                    ->andWhere('u.id = :id')
                    ->setParameter('id', $defaultUrl->getId())
                    ->getQuery()
                    ->getOneOrNullResult();
                if ($parentEntity) {
                    $masterEntity = $parentEntity;
                    break;
                }
            }

            /* Get default locale entity and check existing locale intl */
            if ($masterEntity) {
                foreach ($masterEntity->getUrls() as $url) {
                    $urlLocales[$url->getLocale()] = $url;
                }
            }

            /* Check ans generate non-existent intl */
            foreach ($websiteLocales as $locale) {
                $flush = false;
                $seo = !empty($seoLocales[$locale]) ? $seoLocales[$locale] : new Seo();
                $url = !empty($urlLocales[$locale]) ? $urlLocales[$locale] : null;
                if ($masterEntity && !$url) {
                    $url = new Url();
                    $url->setLocale($locale);
                    $flush = true;
                    $masterEntity->addUrl($url);
                    $this->coreLocator->em()->persist($masterEntity);
                    $this->coreLocator->em()->persist($url);
                }
                if (!$url->getSeo()) {
                    $url->setSeo($seo);
                    $flush = true;
                    $this->coreLocator->em()->persist($url);
                }
                if ($flush) {
                    $this->coreLocator->em()->flush();
                }
            }

            $urlTableName = $this->coreLocator->em()->getClassMetadata(Url::class)->getTableName();
            foreach ($masterEntity->getUrls() as $url) {
                if (empty($intls[$tableName][$defaultSeo->getId()][$url->getLocale()])) {
                    $intls[$tableName][$defaultSeo->getId()][$url->getLocale()] = (object) ['entity' => $url->getSeo(), 'intl' => $url->getSeo(), 'isCollection' => false, 'defaultIntl' => $defaultSeo];
                    $intls[$urlTableName][$url->getId()][$url->getLocale()] = (object) ['entity' => $url, 'intl' => $url, 'isCollection' => false, 'defaultIntl' => $defaultUrl];
                }
            }
        }

        return $intls;
    }

    /**
     * Generate non-existent intl.
     *
     * @throws NonUniqueResultException
     */
    private function generateIntls(array $intls, string $defaultLocale, array $websiteLocales): array
    {
        foreach ($intls as $tableName => $entity) {
            $defaultEntity = null;
            $existingLocales = [];
            $intlsLocales = [];
            foreach ($entity as $locales) {
                $defaultEntity = !empty($locales[$defaultLocale]) ? $locales[$defaultLocale] : null;
                $defaultIntl = $defaultEntity ? $defaultEntity->intl : null;
                $entity = $defaultEntity ? $defaultEntity->entity : null;
                $interface = $defaultEntity ? $this->coreLocator->interfaceHelper()->generate(get_class($defaultEntity->entity)) : [];
                $masterField = !empty($interface['masterField']) ? $interface['masterField'] : (!empty($interface['actionCode']) ? $interface['actionCode'] : null);
                $masterFieldGetter = $masterField ? 'get'.ucfirst($masterField) : null;
                $masterEntity = $masterFieldGetter && method_exists($entity, $masterFieldGetter) && $entity->$masterFieldGetter() ? $entity->$masterFieldGetter() : null;
                $entityConfiguration = $masterEntity
                    ? $this->coreLocator->em()->getRepository(Entity::class)->optimizedQuery(str_replace('Proxies\__CG__\\', '', get_class($masterEntity)), $this->coreLocator->website())
                    : false;
                $isMediaMulti = $entityConfiguration && $entityConfiguration->isMediaMulti() && str_contains($tableName, 'media');
                /* Get default locale entity and check existing locale intl */
                foreach ($locales as $locale => $infos) {
                    if ($isMediaMulti) {
                        foreach ($masterEntity->getMediaRelations() as $mediaRelation) {
                            if ($mediaRelation->getPosition() === $entity->getPosition()) {
                                $intlsLocales[$mediaRelation->getLocale()] = $mediaRelation->getIntl();
                                $existingLocales[] = $mediaRelation->getLocale();
                            }
                        }
                    } else {
                        $existingLocales[] = $locale;
                        $intlsLocales[$locale] = $infos->intl;
                    }
                }
                /* Check ans generate non-existent intl */
                foreach ($websiteLocales as $locale) {
                    if ($entity && $defaultEntity) {
                        if ($defaultIntl && !in_array($locale, $existingLocales)) {
                            $isCollection = $defaultEntity->isCollection;
                            if ($isMediaMulti) {
                                $intl = $this->addIntl(false, $tableName, $entity, $locale, $defaultIntl, $isMediaMulti);
                            } else {
                                $intl = $this->addIntl($isCollection, $tableName, $entity, $locale, $defaultIntl);
                            }
                            $intls[$tableName][$entity->getId()][$locale] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => false, 'defaultIntl' => $defaultIntl];
                        } else {
                            $intls[$tableName][$entity->getId()][$locale] = (object) ['entity' => $entity, 'intl' => $intlsLocales[$locale], 'isCollection' => false, 'defaultIntl' => $defaultIntl];
                        }
                    }
                }
            }
        }

        return $intls;
    }

    /**
     * Add intl.
     *
     * @throws NonUniqueResultException
     */
    private function addIntl(
        bool $isCollection,
        string $tableName,
        mixed $entity,
        string $locale,
        mixed $defaultIntl = null,
        bool $isMediaMulti = false,
    ): mixed {

        $intlData = method_exists($entity, 'getIntls')
            ? $this->coreLocator->metadata($entity, 'intls')
            : $this->coreLocator->metadata($entity, 'intl');
        $excluded = ['id', 'createdAt', 'updatedAt', 'computeETag'];
        $defaultIntl = $defaultIntl ?: new ($intlData->targetEntity)();

        if (
            ($entity && method_exists($entity, 'getIntl') && $entity->getIntl() && $locale === $entity->getIntl()->getLocale())
            || (method_exists($entity, 'getLocale') && $entity->getLocale() && $locale === $entity->getLocale())
        ) {
            return $entity;
        }

        if (!$isCollection) {

            $interface = $this->coreLocator->interfaceHelper()->generate(get_class($entity));
            $masterField = !empty($interface['masterField']) ? $interface['masterField'] : (!empty($interface['actionCode']) ? $interface['actionCode'] : null);
            $metadata = $this->coreLocator->em()->getClassMetadata(get_class($entity));

            $intlEntity = new ($metadata->name)();
            foreach ($metadata->fieldNames as $fieldName) {
                if (!in_array($fieldName, $excluded)) {
                    $intlSetter = 'set'.ucfirst($fieldName);
                    $intlGetter = 'get'.ucfirst($fieldName);
                    $intlGetter = method_exists($entity, $intlGetter) ? 'get'.ucfirst($fieldName) : 'is'.ucfirst($fieldName);
                    $intlEntity->$intlSetter($entity->$intlGetter());
                }
            }
            if (method_exists($intlEntity, 'setLocale')) {
                $intlEntity->setLocale($locale);
            }
            if ($masterField) {
                $getter = 'get'.ucfirst($masterField);
                $setter = 'set'.ucfirst($masterField);
                if (method_exists($intlEntity, $setter)) {
                    $intlEntity->$setter($entity->$getter());
                    if (str_contains(get_class($intlEntity), 'MediaRelation') && $entity->$getter() && method_exists($entity->$getter(), 'getMediaRelations')) {
                        $defaultMedia = null;
                        foreach ($entity->$getter()->getMediaRelations() as $mediaRelation) {
                            if ($mediaRelation->getLocale() === $this->website->getConfiguration()->getLocale()) {
                                $defaultMedia = $mediaRelation->getMedia();
                                break;
                            }
                        }
                        if ($defaultMedia instanceof Media && method_exists($intlEntity, 'setMedia')) {
                            $intlEntity->setMedia($defaultMedia);
                        }
                    }
                    if ($isMediaMulti) {
                        $masterEntity = $entity->$getter();
                        $masterEntity->addMediaRelation($intlEntity);
                        $this->coreLocator->em()->persist($masterEntity);
                    }
                }
            }

            $entity = $intlEntity;
        }

        $newIntl = new ($intlData->targetEntity)();
        $newIntl->setLocale($locale);
        if (method_exists($newIntl, 'setTitleForce') && method_exists($defaultIntl, 'getTitleForce')) {
            $newIntl->setTitleForce($defaultIntl->getTitleForce());
        }
        if (method_exists($newIntl, 'setTargetStyle') && method_exists($defaultIntl, 'getTargetStyle')) {
            $newIntl->setTargetStyle($defaultIntl->getTargetStyle());
        }
        if (method_exists($newIntl, 'setTargetPage') && method_exists($defaultIntl, 'getTargetPage')) {
            $newIntl->setTargetPage($defaultIntl->getTargetPage());
        }
        if (method_exists($newIntl, 'setPosition') && method_exists($defaultIntl, 'setPosition')) {
            $newIntl->setPosition($defaultIntl->setPosition());
        }
        if (method_exists($newIntl, 'setWebsite')) {
            $newIntl->setWebsite($this->website);
        }

        $setter = $isCollection ? 'addIntl' : 'setIntl';
        $entity->$setter($newIntl);

        $this->coreLocator->em()->persist($entity);
        $this->coreLocator->em()->flush();

        return $newIntl;
    }

    /**
     * Generate intls CSV.
     *
     * @throws MappingException|\PhpOffice\PhpSpreadsheet\Exception|Exception
     */
    private function generateCsvIntls(array $intls, string $defaultLocale): void
    {
        $fileData = $this->getIntlFileData($intls, $defaultLocale);
        foreach ($fileData as $tableName => $locales) {
            foreach ($locales as $locale => $entities) {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setCellValue($this->getCsvIntlsIndex('locale', $tableName). 1, 'locale');
                $sheet->getColumnDimension($this->getCsvIntlsIndex('locale', $tableName))->setAutoSize(true);
                $sheet->setCellValue($this->getCsvIntlsIndex('website', $tableName). 1, 'website');
                $sheet->getColumnDimension($this->getCsvIntlsIndex('locale', $tableName))->setAutoSize(true);
                $intlFields = !empty($entities[0]) ? $entities[0]['intlFields'] : [];
                foreach ($intlFields as $field) {
                    if (!empty($this->getCsvIntlsIndex($field->field, $tableName))) {
                        $sheet->setCellValue($this->getCsvIntlsIndex($field->field, $tableName). 1, $field->field);
                        $sheet->getColumnDimension($this->getCsvIntlsIndex($field->field, $tableName))->setAutoSize(true);
                        foreach ($entities as $entityKey => $entity) {
                            $sheet->setCellValue($this->getCsvIntlsIndex('locale', $tableName).($entityKey + 2), $locale);
                            $sheet->setCellValue($this->getCsvIntlsIndex('website', $tableName).($entityKey + 2), $this->website->getId());
                            $sheet->setCellValue($this->getCsvIntlsIndex($field->field, $tableName).($entityKey + 2), $this->normalizeAndDecode($entity[$field->field]));
                        }
                    }
                }
                $filename = $tableName.'-'.$locale.'.xlsx';
                $excelFilepath = $this->dirname.'/'.$filename;
                $writer = new Xlsx($spreadsheet);
                $writer->save($excelFilepath);
            }
        }
    }

    /**
     * Generate intls file data.
     *
     * @throws MappingException
     */
    private function getIntlFileData(array $intls, string $defaultLocale): array
    {
        $fileData = [];

        foreach ($intls as $tableName => $entity) {
            foreach ($entity as $locales) {
                foreach ($locales as $locale => $info) {
                    if ($locale !== $defaultLocale) {
                        if (property_exists($info, 'defaultIntl')) {
                            $defaultIntl = $info->defaultIntl;
                            $localeIntl = $info->intl;
                            $intlFields = $this->getIntlFields($localeIntl);
                            $defaultCount = $this->getIntlContentCount($defaultIntl, $intlFields);
                            $haveContent = $this->getIntlHaveContent($defaultIntl, $localeIntl, $intlFields);
                            if ($defaultCount > 0 && $haveContent) {
                                $entityData = [];
                                $entityData['intlFields'] = $intlFields;
                                foreach ($intlFields as $field) {
                                    $getter = $field->getter;
                                    if ('id' === $field->field) {
                                        $entityData['id'] = $localeIntl->getId();
                                    } else {
                                        $localeContentLength = strlen(strip_tags((string)$localeIntl->$getter()));
                                        $entityData[$field->field] = 0 === $localeContentLength ? $defaultIntl->$getter() : null;
                                    }
                                }
                                $fileData[$tableName][$locale][] = $entityData;
                            }
                        }
                    }
                }
            }
        }

        return $fileData;
    }

    /**
     * Get fields content count.
     */
    private function getIntlContentCount(mixed $intl, array $intlFields): int
    {
        $count = 0;
        foreach ($intlFields as $field) {
            $getter = $field->getter;
            $intl = $intl && method_exists($intl, 'getIntl') ? $intl->getIntl() : $intl;
            if (!$intl) {
                return $count;
            }
            $contentLength = strlen(strip_tags((string)$intl->$getter()));
            if ($contentLength > 0 && 'id' !== $field->field) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Check if have content to translate.
     */
    private function getIntlHaveContent(mixed $defaultIntl, mixed $localeIntl, array $intlFields): bool
    {
        foreach ($intlFields as $field) {
            $getter = $field->getter;
            $defaultIntl = $defaultIntl && method_exists($defaultIntl, 'getIntl') ? $defaultIntl->getIntl() : $defaultIntl;
            if (!$defaultIntl) {
                return false;
            }
            $defaultContentLength = strlen(strip_tags((string)$defaultIntl->$getter()));
            $localeContentLength = strlen(strip_tags((string)$localeIntl->$getter()));
            if ('id' !== $field->field && $defaultContentLength > 0 && 0 === $localeContentLength) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get column index.
     */
    private function getCsvIntlsIndex(string $column, string $tableName): mixed
    {
        $tableName = str_replace($_ENV['DATABASE_PREFIX'].'_', '', $tableName);

        $indexes = [
            'locale' => 'A',
            'website' => 'B',
            'id' => 'C',
            'title' => 'D',
            'subTitle' => 'E',
            'introduction' => 'F',
            'body' => 'G',
            'targetLink' => 'H',
            'targetLabel' => 'I',
            'placeholder' => 'J',
            'help' => 'K',
            'error' => 'L',
        ];

        if ('seo' === $tableName) {
            $indexes = [
                'locale' => 'A',
                'website' => 'B',
                'id' => 'C',
                'metaTitle' => 'D',
                'metaTitleSecond' => 'E',
                'breadcrumbTitle' => 'F',
                'metaDescription' => 'G',
                'keywords' => 'H',
                'author' => 'I',
                'authorType' => 'J',
                'footerDescription' => 'K',
                'metaCanonical' => 'L',
                'metaOgTitle' => 'M',
                'metaOgDescription' => 'N',
            ];
        } elseif ('seo_url' === $tableName) {
            $indexes = [
                'locale' => 'A',
                'website' => 'B',
                'id' => 'C',
                'code' => 'D',
            ];
        }

        return !empty($indexes[$column]) ? $indexes[$column] : null;
    }

    /**
     * Get Translations.
     */
    private function getTranslations(string $defaultLocale): array
    {
        $translations = [];
        $domains = $this->coreLocator->em()->getRepository(TranslationDomain::class)->findAll();

        foreach ($domains as $domain) {
            if ($domain->isExtract()) {
                foreach ($domain->getUnits() as $unit) {
                    foreach ($unit->getTranslations() as $translation) {
                        if ($translation->getLocale() !== $defaultLocale && !$translation->getContent()) {
                            $translations[$translation->getLocale()][] = $translation;
                        }
                    }
                }
            }
        }

        return $translations;
    }

    /**
     * Generate translations CSV.
     *
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function generateCsvTranslations(array $translations, string $defaultLocale): void
    {
        foreach ($translations as $locale => $localeTranslation) {

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'locale');
            $sheet->setCellValue('B1', 'domain');
            $sheet->setCellValue('C1', 'id');
            $sheet->setCellValue('D1', 'content');
            $sheet->setCellValue('E1', 'translation');

            foreach ($localeTranslation as $key => $translation) {
                /** @var Translation $translation */
                $defaultContent = null;
                foreach ($translation->getUnit()->getTranslations() as $unitTranslation) {
                    if ($unitTranslation->getLocale() === $defaultLocale) {
                        $defaultContent = $this->normalizeAndDecode($unitTranslation->getContent());
                        break;
                    }
                }
                if ($defaultContent) {
                    $sheet->setCellValue('A'.($key + 2), $translation->getLocale());
                    $sheet->setCellValue('B'.($key + 2), $translation->getUnit()->getDomain()->getName());
                    $sheet->setCellValue('C'.($key + 2), $translation->getId());
                    $sheet->setCellValue('D'.($key + 2), $defaultContent);
                    $sheet->setCellValue('E'.($key + 2), '');
                }
            }

            $excelFilepath = $this->dirname.'/translations-'.$locale.'.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save($excelFilepath);
        }
    }

    /**
     * Get intl text fields.
     *
     * @throws MappingException
     */
    private function getIntlFields(mixed $entity): array
    {
        $referIntl = new (get_class($entity))();
        $intlMetadata = $this->coreLocator->em()->getClassMetadata(get_class($entity));
        $intlAllFields = $intlMetadata->getFieldNames();
        $allowedFields = ['string', 'text'];
        $disallowedFields = ['subTitlePosition', 'pictogram', 'video', 'associatedWords', 'authorType', 'targetStyle', 'slug'];

        $intlFields = [];
        foreach ($intlAllFields as $field) {
            $getter = 'get'.ucfirst($field);
            $mapping = $intlMetadata->getFieldMapping($field);
            $isText = in_array($mapping['type'], $allowedFields) && !str_contains(strtolower($mapping['fieldName']), 'alignment') && 'locale' !== $field;
            if (method_exists($referIntl, $getter) && $isText && !in_array($field, $disallowedFields) || 'id' === $field) {
                $intlFields[] = (object) ['getter' => $getter, 'field' => $field];
            }
        }

        return $intlFields;
    }

    /**
     * Normalize a value to UTF-8 text and decode HTML entities.
     * Returns original value for numbers, booleans, null, empty strings, or arrays (recursively processed).
     */
    function normalizeAndDecode($value): mixed
    {
        // If it's an array, process each element recursively
        if (is_array($value)) {
            return array_map('normalizeAndDecode', $value);
        }

        // Pass through numbers, booleans, null, and empty strings unchanged
        if (is_int($value) || is_numeric($value) || is_bool($value) || $value === null || $value === '') return $value;

        // Ensure we only handle strings below; if not a string (e.g., object without __toString), return as-is
        if (!is_string($value)) {
            return $value;
        }

        // 1) If it's not valid UTF-8, detect the source encoding and convert to UTF-8
        if (!mb_check_encoding($value, 'UTF-8')) {
            // Likely encodings for legacy French content
            $enc = mb_detect_encoding($value, ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15', 'UTF-8'], true) ?: 'Windows-1252';

            // Convert with iconv (ignore invalid bytes); fallback to mb_convert_encoding
            $converted = @iconv($enc, 'UTF-8//IGNORE', $value);
            if ($converted === false) {
                $converted = @mb_convert_encoding($value, 'UTF-8', $enc);
            }
            $value = $converted;
        }

        // 2) Strip non-printable ASCII control chars (safer for Excel/CSV consumers)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        // 3) Decode HTML entities (named, decimal, hex). Up to 3 passes to handle double-encoding.
        for ($i = 0; $i < 3; $i++) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $value) break; // nothing more to decode
            $value = $decoded;
        }

        return $value;
    }
}
