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
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TranslationExcelGenerator $excelGenerator
    ) {
        $dirname = $this->coreLocator->projectDir().'/bin/export';
        $this->dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
    }

    /**
     * Execute exportation.
     *
     * @throws MappingException|NonUniqueResultException
     */
    public function execute(Website $website): void
    {
        $this->website = $website;

        $this->removeXlsxFiles();

        $defaultLocale = $website->getConfiguration()->getLocale();
        $locales = $website->getConfiguration()->getLocales();

        $intls = $this->getIntls();
        $intls = $this->generateSeo($intls, $defaultLocale, $locales);
        $intls = $this->generateIntls($intls, $defaultLocale, $locales);
        
        $fileData = $this->getIntlFileData($intls, $defaultLocale);
        $this->excelGenerator->generateIntlFiles($fileData, $this->website, $this->dirname);

        $translations = $this->getTranslations($defaultLocale);
        $this->excelGenerator->generateTranslationFiles($translations, $defaultLocale, $this->dirname);
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
     * Get all internationalized entities.
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
        $em = $this->coreLocator->em();
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $intls = [];

        // Pre-load Pages of the current website to index Layouts (N+1 optimization for Blocks)
        $pages = $em->getRepository(Page::class)->findBy(['website' => $this->website]);
        $layoutToPage = [];
        foreach ($pages as $page) {
            if ($page->getLayout()) {
                $layoutToPage[$page->getLayout()->getId()] = $page;
            }
        }

        foreach ($metadata as $data) {
            $namespace = $data->getName();
            if (0 === $data->getReflectionClass()->getModifiers() && !in_array($namespace, $excluded)) {
                $reflection = $data->getReflectionClass();
                $tableName = $data->getTableName();
                $hasGetIntls = $reflection->hasMethod('getIntls');
                $hasGetIntl = $reflection->hasMethod('getIntl');

                if ($hasGetIntls || $hasGetIntl) {
                    $repository = $em->getRepository($namespace);
                    if ($reflection->hasMethod('getWebsite')) {
                        $entities = $repository->findBy(['website' => $this->website]);
                    } else {
                        // For entities without getWebsite, try to filter by media if possible
                        $entities = $repository->findAll();
                        foreach ($entities as $key => $entity) {
                            if (method_exists($entity, 'getMedia') && $entity->getMedia() && $entity->getMedia()->getWebsite() && $entity->getMedia()->getWebsite()->getId() !== $this->website->getId()) {
                                unset($entities[$key]);
                            }
                        }
                    }

                    foreach ($entities as $entity) {
                        $export = true;
                        if ($entity instanceof Block) {
                            $col = $entity->getCol();
                            $zone = $col ? $col->getZone() : null;
                            $layout = $zone ? $zone->getLayout() : null;
                            // Use pre-loaded map instead of findOneBy (optimization)
                            $layoutParent = $layout && isset($layoutToPage[$layout->getId()]) ? $layoutToPage[$layout->getId()] : null;
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
                            if ($hasGetIntls) {
                                foreach ($entity->getIntls() as $intl) {
                                    $intls[$tableName][$entity->getId()][$intl->getLocale()] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => true];
                                }
                            } else {
                                $intl = $entity->getIntl() ?: $this->addIntl(false, $entity, $entity->getLocale());
                                if ($intl) {
                                    $intls[$tableName][$entity->getId()][$intl->getLocale()] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => false];
                                }
                            }
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
        $em = $this->coreLocator->em();
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $namespaces = [];
        foreach ($metadata as $data) {
            $namespace = $data->getName();
            if (0 === $data->getReflectionClass()->getModifiers()) {
                if ($data->getReflectionClass()->hasMethod('getUrls')) {
                    $namespaces[] = $namespace;
                }
            }
        }

        // N+1 Optimization: Create a global map of Url ID -> Master Entity for ALL relevant namespaces.
        // This avoids individual queries when looking for which entity an URL belongs to.
        $urlIdToMaster = [];
        foreach ($namespaces as $namespace) {
            $entitiesWithUrls = $em->getRepository($namespace)->createQueryBuilder('e')
                ->select('e, u')
                ->join('e.urls', 'u')
                ->andWhere('u.website = :website')
                ->setParameter('website', $this->website)
                ->getQuery()
                ->getResult();
            foreach ($entitiesWithUrls as $entity) {
                foreach ($entity->getUrls() as $url) {
                    $urlIdToMaster[$url->getId()] = $entity;
                }
            }
        }

        $seoMetadata = $em->getClassMetadata(Seo::class);
        $tableName = $seoMetadata->getTableName();
        $entities = $em->getRepository(Seo::class)->createQueryBuilder('e')
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

        $urlMetadata = $em->getClassMetadata(Url::class);
        $urlTableName = $urlMetadata->getTableName();

        foreach ($intls[$tableName] as $locales) {

            $urlLocales = [];
            $defaultSeo = !empty($locales[$defaultLocale]) ? $locales[$defaultLocale]->intl : null;
            if (!$defaultSeo) {
                continue;
            }
            $defaultUrl = $defaultSeo->getUrl();

            /* Get master entity from preloaded map */
            $masterEntity = !empty($urlIdToMaster[$defaultUrl->getId()]) ? $urlIdToMaster[$defaultUrl->getId()] : null;

            /* Get the default locale entity and check existing locale intl */
            if ($masterEntity) {
                foreach ($masterEntity->getUrls() as $url) {
                    $urlLocales[$url->getLocale()] = $url;
                }
            }

            /* Check and generate non-existent intl */
            $needsFlush = false;
            foreach ($websiteLocales as $locale) {
                $url = !empty($urlLocales[$locale]) ? $urlLocales[$locale] : null;
                if ($masterEntity && !$url) {
                    $url = new Url();
                    $url->setLocale($locale);
                    $url->setWebsite($this->website);
                    $masterEntity->addUrl($url);
                    $em->persist($masterEntity);
                    $em->persist($url);
                    $needsFlush = true;
                }
                if ($url && !$url->getSeo()) {
                    $seo = new Seo();
                    $url->setSeo($seo);
                    $em->persist($url);
                    $em->persist($seo);
                    $needsFlush = true;
                }
            }
            if ($needsFlush) {
                $em->flush();
            }

            if ($masterEntity) {
                foreach ($masterEntity->getUrls() as $url) {
                    if (empty($intls[$tableName][$defaultSeo->getId()][$url->getLocale()])) {
                        $intls[$tableName][$defaultSeo->getId()][$url->getLocale()] = (object) ['entity' => $url->getSeo(), 'intl' => $url->getSeo(), 'isCollection' => false, 'defaultIntl' => $defaultSeo];
                        $intls[$urlTableName][$url->getId()][$url->getLocale()] = (object) ['entity' => $url, 'intl' => $url, 'isCollection' => false, 'defaultIntl' => $defaultUrl];
                    }
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
        $interfaceHelper = $this->coreLocator->interfaceHelper();
        $em = $this->coreLocator->em();
        $entityRepo = $em->getRepository(Entity::class);
        $interfaces = [];
        $entityConfigurations = [];

        foreach ($intls as $tableName => $tableEntities) {
            foreach ($tableEntities as $entityId => $locales) {
                $defaultEntity = !empty($locales[$defaultLocale]) ? $locales[$defaultLocale] : null;
                if (!$defaultEntity) {
                    continue;
                }
                
                $defaultIntl = $defaultEntity->intl;
                $entity = $defaultEntity->entity;
                $entityClass = get_class($entity);

                if (!isset($interfaces[$entityClass])) {
                    $interfaces[$entityClass] = $interfaceHelper->generate($entityClass);
                }
                $interface = $interfaces[$entityClass];

                $masterField = !empty($interface['masterField']) ? $interface['masterField'] : (!empty($interface['actionCode']) ? $interface['actionCode'] : null);
                $masterFieldGetter = $masterField ? 'get'.ucfirst($masterField) : null;
                $masterEntity = $masterFieldGetter && method_exists($entity, $masterFieldGetter) ? $entity->$masterFieldGetter() : null;
                
                $isMediaMulti = false;
                if ($masterEntity) {
                    $masterClass = str_replace('Proxies\__CG__\\', '', get_class($masterEntity));
                    if (!isset($entityConfigurations[$masterClass])) {
                        $entityConfigurations[$masterClass] = $entityRepo->optimizedQuery($masterClass, $this->coreLocator->website());
                    }
                    $entityConfiguration = $entityConfigurations[$masterClass];
                    $isMediaMulti = $entityConfiguration && $entityConfiguration->isMediaMulti() && str_contains($tableName, 'media');
                }

                $existingLocales = [];
                $intlsLocales = [];

                /* Get existing locale intl */
                foreach ($locales as $locale => $infos) {
                    if ($isMediaMulti && $masterEntity) {
                        foreach ($masterEntity->getMediaRelations() as $mediaRelation) {
                            if ($mediaRelation->getPosition() === $entity->getPosition()) {
                                $intlsLocales[$mediaRelation->getLocale()] = $mediaRelation->getIntl();
                                if (!in_array($mediaRelation->getLocale(), $existingLocales)) {
                                    $existingLocales[] = $mediaRelation->getLocale();
                                }
                            }
                        }
                    } else {
                        $existingLocales[] = $locale;
                        $intlsLocales[$locale] = $infos->intl;
                    }
                }

                /* Check and generate non-existent intl */
                $needsFlush = false;
                foreach ($websiteLocales as $locale) {
                    if (!in_array($locale, $existingLocales)) {
                        $isCollection = $defaultEntity->isCollection;
                        $intl = $this->addIntl($isCollection, $entity, $locale, $defaultIntl, $isMediaMulti, false);
                        $intls[$tableName][$entityId][$locale] = (object) ['entity' => $entity, 'intl' => $intl, 'isCollection' => false, 'defaultIntl' => $defaultIntl];
                        $needsFlush = true;
                    } elseif (isset($intlsLocales[$locale])) {
                        $intls[$tableName][$entityId][$locale] = (object) ['entity' => $entity, 'intl' => $intlsLocales[$locale], 'isCollection' => false, 'defaultIntl' => $defaultIntl];
                    }
                }
                if ($needsFlush) {
                    $em->flush();
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
        mixed $entity,
        string $locale,
        mixed $defaultIntl = null,
        bool $isMediaMulti = false,
        bool $flush = true,
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
        if ($flush) {
            $this->coreLocator->em()->flush();
        }

        return $newIntl;
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
     * Check if you have content to translate.
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
     * Get Translations.
     */
    private function getTranslations(string $defaultLocale): array
    {
        $translations = [];
        $domains = $this->coreLocator->em()->getRepository(TranslationDomain::class)->findAll();

        foreach ($domains as $domain) {
            if ($domain->isExtract()) {
                $units = $this->coreLocator->em()->getRepository(Translation::class)->createQueryBuilder('t')
                    ->select('t, u')
                    ->join('t.unit', 'u')
                    ->where('u.domain = :domain')
                    ->setParameter('domain', $domain)
                    ->getQuery()
                    ->getResult();

                foreach ($units as $translation) {
                    if ($translation->getLocale() !== $defaultLocale && !$translation->getContent()) {
                        $translations[$translation->getLocale()][] = $translation;
                    }
                }
            }
        }

        return $translations;
    }

    /**
     * Get intl text fields.
     *
     * @throws MappingException
     */
    private function getIntlFields(mixed $entity): array
    {
        if (!$entity) {
            return [];
        }

        $entityClass = get_class($entity);
        $intlMetadata = $this->coreLocator->em()->getClassMetadata($entityClass);
        $intlAllFields = $intlMetadata->getFieldNames();
        $allowedFields = ['string', 'text'];
        $disallowedFields = ['subTitlePosition', 'pictogram', 'video', 'associatedWords', 'authorType', 'targetStyle', 'slug'];

        $intlFields = [];
        foreach ($intlAllFields as $field) {
            $getter = 'get'.ucfirst($field);
            $mapping = $intlMetadata->getFieldMapping($field);
            $isText = in_array($mapping['type'], $allowedFields) && !str_contains(strtolower($mapping['fieldName']), 'alignment') && 'locale' !== $field;
            
            // Check if method exists on the entity itself or reflection
            if (($intlMetadata->getReflectionClass()->hasMethod($getter) || method_exists($entity, $getter)) && $isText && !in_array($field, $disallowedFields) || 'id' === $field) {
                $intlFields[] = (object) ['getter' => $getter, 'field' => $field];
            }
        }

        return $intlFields;
    }

    /**
     * Collect translatable groups (intl + translations) without writing any file.
     *
     * Each group: ['type' => 'intl'|'translation', 'locale', 'group' (label),
     * 'class' (intl FQCN, intl only), 'items' => [['ref', 'source', 'html']]].
     * Provisions missing intl rows as a side effect, like the export.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws MappingException|NonUniqueResultException
     */
    public function collectTranslatable(Website $website, int $chunkSize = 25): array
    {
        $this->website = $website;
        $defaultLocale = $website->getConfiguration()->getLocale();
        $locales = $website->getConfiguration()->getLocales();

        $intls = $this->getIntls();
        $intls = $this->generateSeo($intls, $defaultLocale, $locales);
        $intls = $this->generateIntls($intls, $defaultLocale, $locales);

        $groups = [];
        $this->appendIntlGroups($groups, $intls, $defaultLocale, $chunkSize);
        $this->appendTranslationGroups($groups, $defaultLocale, $chunkSize);

        return $groups;
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     *
     * @throws MappingException
     */
    private function appendIntlGroups(array &$groups, array $intls, string $defaultLocale, int $chunkSize): void
    {
        $em = $this->coreLocator->em();
        $perClass = [];

        foreach ($intls as $tableEntities) {
            foreach ($tableEntities as $localesInfo) {
                foreach ($localesInfo as $locale => $info) {
                    if ($locale === $defaultLocale || !property_exists($info, 'defaultIntl')) {
                        continue;
                    }
                    $localeIntl = $info->intl ?? null;
                    $defaultIntl = $info->defaultIntl ?? null;
                    if (!$localeIntl || !$defaultIntl || !$localeIntl->getId()) {
                        continue;
                    }
                    $class = $em->getClassMetadata(get_class($localeIntl))->getName();
                    foreach ($this->getIntlFields($localeIntl) as $field) {
                        if ('id' === $field->field) {
                            continue;
                        }
                        $getter = $field->getter;
                        $source = (string) $defaultIntl->$getter();
                        $current = (string) $localeIntl->$getter();
                        if ('' === trim(strip_tags($source)) || '' !== trim(strip_tags($current))) {
                            continue;
                        }
                        $perClass[$class][$locale][] = [
                            'ref' => ['id' => $localeIntl->getId(), 'field' => $field->field],
                            'source' => $source,
                            'html' => $source !== strip_tags($source),
                        ];
                    }
                }
            }
        }

        foreach ($perClass as $class => $byLocale) {
            $label = preg_replace('/Intl$/', '', (new \ReflectionClass($class))->getShortName());
            foreach ($byLocale as $locale => $items) {
                foreach (array_chunk($items, $chunkSize) as $chunk) {
                    $groups[] = ['type' => 'intl', 'locale' => $locale, 'group' => $label, 'class' => $class, 'items' => $chunk];
                }
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     */
    private function appendTranslationGroups(array &$groups, string $defaultLocale, int $chunkSize): void
    {
        $byLocale = $this->getTranslations($defaultLocale);
        if (!$byLocale) {
            return;
        }

        $em = $this->coreLocator->em();
        $unitIds = [];
        foreach ($byLocale as $list) {
            foreach ($list as $translation) {
                $unitIds[$translation->getUnit()->getId()] = true;
            }
        }

        $defaults = [];
        if ($unitIds) {
            $rows = $em->getRepository(Translation::class)->createQueryBuilder('t')
                ->select('t, u')
                ->join('t.unit', 'u')
                ->where('t.locale = :locale')
                ->andWhere('u.id IN (:ids)')
                ->setParameter('locale', $defaultLocale)
                ->setParameter('ids', array_keys($unitIds))
                ->getQuery()
                ->getResult();
            foreach ($rows as $row) {
                $defaults[$row->getUnit()->getId()] = $row->getContent();
            }
        }

        $perDomain = [];
        foreach ($byLocale as $locale => $list) {
            foreach ($list as $translation) {
                $unit = $translation->getUnit();
                $source = $defaults[$unit->getId()] ?? null;
                if (null === $source || '' === trim((string) $source)) {
                    continue;
                }
                $perDomain[$locale][$unit->getDomain()->getName()][] = [
                    'ref' => ['keyName' => $unit->getKeyName()],
                    'source' => (string) $source,
                    'html' => (string) $source !== strip_tags((string) $source),
                ];
            }
        }

        foreach ($perDomain as $locale => $domains) {
            foreach ($domains as $domainName => $items) {
                foreach (array_chunk($items, $chunkSize) as $chunk) {
                    $groups[] = ['type' => 'translation', 'locale' => $locale, 'group' => $domainName, 'items' => $chunk];
                }
            }
        }
    }
}
