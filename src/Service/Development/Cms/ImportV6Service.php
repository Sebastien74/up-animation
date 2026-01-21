<?php

declare(strict_types=1);

namespace App\Service\Development\Cms;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Layout\Page;
use App\Entity\Media\Folder;
use App\Entity\Media\Media;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Faq\Faq;
use App\Entity\Module\Faq\Question;
use App\Entity\Module\Form\Configuration;
use App\Entity\Module\Form\Form;
use App\Entity\Module\Menu\Link;
use App\Entity\Module\Menu\Menu;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Service\Core\Urlizer;
use Symfony\Component\Filesystem\Filesystem;

/**
 * ImportV6Service.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImportV6Service extends ImportService
{
    private const array BLOCK_TYPES_ACTIONS = [
        'titleheader' => 'title-header',
        'event-index' => 'catalog-index',
        'event-teaser' => 'catalog-teaser',
    ];

    /**
     * To set core.
     */
    public function setCore(Website $website, string $classname, string $table, string $sort = 'id', string $order = 'ASC'): void
    {
        $this->classname = $classname;
        $this->setEntities($table, $sort, $order);
    }

    /**
     * To set entities.
     */
    protected function setEntities(string $table, string $sort = 'id', string $order = 'ASC'): void
    {
        $this->entities = $this->sqlService->findAll($table, $sort, $order);
    }

    /**
     * To set default Properties.
     *
     * @throws \Exception
     */
    protected function setProperties(
        mixed $entity,
        Website $website,
        array $entityToImport = [],
        ?int $position = null,
        ?string $relationPrefix = null): void
    {
        $prefix = $this->sqlService->prefix();

        if (method_exists($entity, 'setWebsite')) {
            $entity->setWebsite($website);
        }

        if (method_exists($entity, 'setPosition')) {
            $position = $position ?: $this->setPosition($website, get_class($entity));
            if (1 === $entity->getPosition() && $position > 1) {
                $entity->setPosition($position);
            }
        }

        $excluded = ['id', 'position', 'computeETag'];
        foreach ($entityToImport as $property => $value) {
            if (!in_array($property, $excluded) && !str_contains($property, '_id')) {
                $asIs = str_starts_with($property, 'is');
                $setter = $asIs ? 'set'.ucfirst(ltrim($property, 'is')) : 'set'.ucfirst($property);
                $setter = $asIs && !method_exists($entity, $setter) ? 'setAs'.ucfirst(ltrim($property, 'is')) : $setter;
                if (method_exists($entity, $setter)) {
                    $metadata = $this->coreLocator->em()->getClassMetadata(get_class($entity));
                    $asMediaCategory = str_contains(get_class($entity), 'MediaRelation') && 'category' === $property;
                    $mapping = !$asMediaCategory && !$asIs ? $metadata->getFieldMapping($property) : ['type' => ''];
                    if ($value && ('publicationStart' === $property || 'publicationEnd' === $property || 'createdAt' === $property || 'updatedAt' === $property)) {
                        $value = new \DateTime($value);
                    } elseif ('grid' === $property) {
                        $value = (array) json_decode($value);
                    } elseif ('json' === $mapping['type']) {
                        $value = str_contains($value, '[') ? json_decode($value) : unserialize($value);
                    } elseif ('boolean' === $mapping['type'] || str_starts_with($property, 'is')) {
                        $value = is_numeric($value) ? (bool) $value : ($value ?: false);
                    }
                    $entity->$setter($value);
                }
            }
        }

        if (method_exists($entity, 'getUrls') && $entity->getUrls()->isEmpty()) {
            $matches = explode('_', $relationPrefix);
            $urlsToImport = $this->sqlService->findBy($prefix.'_'.$relationPrefix.'_urls', end($matches).'_id', $entityToImport['id'], 'url_id', 'ASC');
            $urlsToImport = !$urlsToImport && 'layout_page' === $relationPrefix ? $this->sqlService->findBy($prefix.'_content_page_urls', end($matches).'_id', $entityToImport['id'], 'url_id', 'ASC') : $urlsToImport;
            foreach ($urlsToImport as $urlToImport) {
                $urlToImport = $this->sqlService->find($prefix.'_seo_url', 'id', $urlToImport['url_id']);
                $url = new Url();
                $this->setProperties($url, $website, $urlToImport);
                $seoToImport = $this->sqlService->find($prefix.'_seo', 'id', $urlToImport['seo_id']);
                $seo = new Seo();
                $this->setProperties($seo, $website, $seoToImport);
                $url->setSeo($seo);
                $entity->addUrl($url);
            }
        }

        if (method_exists($entity, 'getIntls') && $entity->getIntls()->isEmpty()) {
            $column = $this->sqlService->relationName($prefix.'_'.$relationPrefix.'_i18ns', 'i18n');
            $intlsToImport = $column ? $this->sqlService->findBy($prefix.'_'.$relationPrefix.'_i18ns', $column, $entityToImport['id'], $column, 'ASC') : [];
            foreach ($intlsToImport as $intlToImport) {
                $intlToImport = $this->sqlService->find($prefix.'_translation_i18n', 'id', $intlToImport['i18n_id']);
                $intlData = $this->coreLocator->metadata($entity, 'intls');
                $intl = new ($intlData->targetEntity)();
                $this->setProperties($intl, $website, $intlToImport);
                $entity->addIntl($intl);
            }
        }

        if (method_exists($entity, 'getIntl') && !$entity->getIntl()) {
            $intlToImport = $this->sqlService->find($prefix.'_translation_i18n', 'id', $entityToImport['i18n_id']);
            $intlData = $this->coreLocator->metadata($entity, 'intl');
            $intl = new ($intlData->targetEntity)();
            $this->setProperties($intl, $website, $intlToImport);
            if (!$intl->getLocale()) {
                $locale = !empty($entityToImport['locale']) ? $entityToImport['locale'] : $website->getConfiguration()->getLocale();
                $intl->setLocale($locale);
            }
            if (!empty($intlToImport['targetPage_id']) && method_exists($intl, 'setTargetPage')) {
                $pageToImport = $this->sqlService->find($prefix.'_layout_page', 'id', $intlToImport['targetPage_id']);
                $pageToImport = !$pageToImport ? $this->sqlService->find($prefix.'_content_page', 'id', $intlToImport['targetPage_id']) : [];
                $targetPage = $this->coreLocator->em()->getRepository(Page::class)->findOneBy(['website' => $website, 'slug' => $pageToImport['slug']]);
                $intl->setTargetPage($targetPage);
            }
            $entity->setIntl($intl);
        }

        if (!empty($entityToImport['category_id']) && method_exists($entity, 'getCategory') && !$entity->getCategory() && $relationPrefix) {
            $categoryToImport = $this->sqlService->find($prefix.'_'.$relationPrefix.'_category', 'id', $entityToImport['category_id']);
            $categoryData = $this->coreLocator->metadata($entity, 'category');
            $category = $this->coreLocator->em()->getRepository($categoryData->targetEntity)->findOneBy(['website' => $categoryToImport['website_id'], 'slug' => $categoryToImport['slug']]);
            if (!$category) {
                $category = new ($categoryData->targetEntity)();
                $this->setProperties($category, $website, $categoryToImport, null, $relationPrefix.'_category');
                $this->coreLocator->em()->persist($category);
            }
            $entity->setCategory($category);
        }

        $this->addMediaRelations($entity, $prefix, $website, $relationPrefix, $entityToImport);
        $this->addLayout($entity, $prefix, $website, $entityToImport);

        if ($entity instanceof Product) {
            $this->setProduct($entity, $prefix, $relationPrefix, $website, $entityToImport);
        }

        if ($entity instanceof Form) {
            $this->setForm($entity, $prefix, $relationPrefix, $website, $entityToImport);
        }

        if ($entity instanceof Menu) {
            $this->setMenu($entity, $prefix, $relationPrefix, $website, $entityToImport);
        }

        if ($entity instanceof Faq) {
            $this->setFaq($entity, $prefix, $relationPrefix, $website, $entityToImport);
        }
    }

    /**
     * To add MediaRelations
     *
     * @throws \Exception
     */
    protected function addMediaRelations(
        mixed $entity,
        string $prefix,
        Website $website,
        string $relationPrefix = null,
        array $entityToImport = []
    ): void
    {
        if (method_exists($entity, 'getMediaRelations')) {
            foreach ($entity->getMediaRelations() as $mediaRelation) {
                $entity->removeMediaRelation($mediaRelation);
            }
        }

        if (method_exists($entity, 'getMediaRelations') && $entity->getMediaRelations()->isEmpty()) {
            $column = $this->sqlService->relationName($prefix.'_'.$relationPrefix.'_media_relations', 'mediaRelations');
            $column = !$column ? $this->sqlService->relationName($prefix.'_'.$relationPrefix.'_relations', 'mediaRelations') : $column;
            if ($column) {
                $mediasToImport = $this->sqlService->findBy($prefix.'_'.$relationPrefix.'_media_relations', $column, $entityToImport['id'], $column, 'ASC');
                $mediasToImport = !$mediasToImport ? $this->sqlService->findBy($prefix.'_'.$relationPrefix.'_relations', $column, $entityToImport['id'], $column, 'ASC') : $mediasToImport;
                foreach ($mediasToImport as $mediaToImport) {
                    $mediaRelationToImport = $this->sqlService->find($prefix.'_media_relation', 'id', $mediaToImport['relation_id']);
                    $mediaToImport = $this->sqlService->find($prefix.'_media', 'id', $mediaRelationToImport['media_id']);
                    if ($mediaToImport['filename']) {
                        $folderToImport = $mediaToImport['folder_id'] ? $this->sqlService->find($prefix.'_media_folder', 'id', $mediaToImport['folder_id']) : null;
                        $folder = null;
                        if ($folderToImport) {
                            $folderSlug = !empty($folderToImport['slug']) ? Urlizer::urlize($folderToImport['slug']) : Urlizer::urlize($folderToImport['adminName']);
                            $folder = $this->coreLocator->em()->getRepository(Folder::class)->findOneBy(['slug' => $folderSlug]);
                            $folderPosition = !$folder ? count($this->coreLocator->em()->getRepository(Folder::class)->findBy(['website' => $website, 'level' => 1])) + 1 : $folder->getPosition();
                            $folder = $folder ?: new Folder();
                            $this->setProperties($folder, $website, $folderToImport);
                            $folder->setPosition($folderPosition);
                            $folder->setLevel(1);
                            $this->coreLocator->em()->persist($folder);
                        }
                        $media = $this->coreLocator->em()->getRepository(Media::class)->findOneBy(['website' => $website, 'filename' => strtolower($mediaToImport['filename'])]);
                        if (!$media) {
                            $media = new Media();
                            $this->setProperties($media, $website, $mediaToImport);
                            $media->setFolder($folder);
                            $dirname = $this->coreLocator->projectDir().'/public/uploads/';
                            $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
                            $importDirname = $dirname.'import'.DIRECTORY_SEPARATOR.$mediaToImport['filename'];
                            $websiteDirname = $dirname.$website->getUploadDirname().DIRECTORY_SEPARATOR.strtolower($mediaToImport['filename']);
                            $filesystem = new Filesystem();
                            if ($filesystem->exists($importDirname)) {
                                $filesystem->copy($importDirname, $websiteDirname);
                            }
                        }
                        $intlData = $this->coreLocator->metadata($entity, 'mediaRelations');
                        $mediaRelation = new ($intlData->targetEntity)();
                        $this->setProperties($mediaRelation, $website, $mediaRelationToImport);
                        $mediaRelation->setMedia($media);
                        $entity->addMediaRelation($mediaRelation);
                    }
                }
            }
        }
    }

    /**
     * To add Layout.
     *
     * @throws \Exception
     */
    protected function addLayout(
        mixed $entity,
        string $prefix,
        Website $website,
        array $entityToImport = []
    ): void {

        if (method_exists($entity, 'getLayout') && !$entity instanceof Layout\Zone) {
            $layout = $entity->getLayout();
            if ($layout instanceof Layout\Layout) {
                $this->coreLocator->em()->remove($layout);
            }
            $position = count($this->coreLocator->em()->getRepository(Layout\Layout::class)->findBy(['website' => $website])) + 1;
            $layout = new Layout\Layout();
            $layout->setAdminName($entity->getAdminName());
            $layout->setSlug(Urlizer::urlize($entity->getAdminName()));
            $layout->setPosition($position);
            $layout->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $layout->setCreatedBy($this->user);
            $layout->setWebsite($website);
            $this->coreLocator->em()->persist($layout);

            $zonesToImport = $this->sqlService->findBy($prefix.'_layout_zone', 'layout_id', $entityToImport['layout_id'], 'position', 'ASC');
            foreach ($zonesToImport as $zoneToImport) {
                $zone = new Layout\Zone();
                $this->setProperties($zone, $website, $zoneToImport, $zoneToImport['position'], 'layout_zone');
                $colsToImport = $this->sqlService->findBy($prefix.'_layout_col', 'zone_id', $zoneToImport['id'], 'position', 'ASC');
                foreach ($colsToImport as $colToImport) {
                    $col = new Layout\Col();
                    $this->setProperties($col, $website, $colToImport, $colToImport['position']);
                    $zone->addCol($col);
                    $blocksToImport = $this->sqlService->findBy($prefix.'_layout_block', 'col_id', $colToImport['id'], 'position', 'ASC');
                    foreach ($blocksToImport as $blockToImport) {
                        $block = new Layout\Block();
                        $this->setProperties($block, $website, $blockToImport, $blockToImport['position'], 'layout_block');
                        $blockType = $this->sqlService->find($prefix.'_layout_block_type', 'id', $blockToImport['blockType_id']);
                        $blockTypeSlug = $blockType && !empty(self::BLOCK_TYPES_ACTIONS[$blockType['slug']]) ? self::BLOCK_TYPES_ACTIONS[$blockType['slug']] : ($blockType ? $blockType['slug'] : null);
                        if ('core-action' === $blockTypeSlug) {
                            $actionToImport = $this->sqlService->find($prefix.'_layout_action', 'id', $blockToImport['action_id']);
                            $actionSlug = $actionToImport && !empty(self::BLOCK_TYPES_ACTIONS[$actionToImport['slug']]) ? self::BLOCK_TYPES_ACTIONS[$actionToImport['slug']] : ($actionToImport ? $actionToImport['slug'] : null);
                            $action = $actionToImport ? $this->coreLocator->em()->getRepository(Layout\Action::class)->findOneBy(['slug' => $actionSlug]) : null;
                            $block->setAction($action);
                            $actionIntls = $this->sqlService->findBy($prefix.'_layout_action_i18n', 'block_id', $blockToImport['id'], 'locale', 'ASC');
                            foreach ($actionIntls as $actionIntlToImport) {
                                $actionIntl = new Layout\ActionIntl();
                                $this->setProperties($actionIntl, $website, $actionIntlToImport, null, 'layout_block');
                                $actionIntl->setActionFilter($actionIntlToImport['actionFilter']);
                                $actionIntl->setBlock($block);
                                $block->addActionIntl($actionIntl);
                            }
                        }
                        $blockType = $blockTypeSlug ? $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => $blockTypeSlug]) : null;
                        if ($blockType && str_contains($blockType->getSlug(), 'form-')) {
                            $configurationToImport = $this->sqlService->find($prefix.'_layout_field_configuration', 'block_id', $blockToImport['id']);
                            if ($configurationToImport) {
                                $configuration = new Layout\FieldConfiguration();
                                $this->setProperties($configuration, $website, $configurationToImport);
                                $block->setFieldConfiguration($configuration);
                            }
                            $configuration = $block->getFieldConfiguration();
                            $fieldValuesToImport = $this->sqlService->findBy($prefix.'_layout_field_configuration_value', 'configuration_id', $configurationToImport['id']);
                            if ($fieldValuesToImport && $configuration->getFieldValues()->isEmpty()) {
                                foreach ($fieldValuesToImport as $fieldValueToImport) {
                                    $value = new Layout\FieldValue();
                                    $this->setProperties($value, $website, $fieldValueToImport, null, 'layout_field_configuration_value');
                                    $configuration->addFieldValue($value);
                                }
                            }
                        }
                        $block->setBlockType($blockType);
                        $col->addBlock($block);
                    }
                }
                $layout->addZone($zone);
            }

            $entity->setLayout($layout);
        }
    }

    /**
     * To set Product.
     *
     * @throws \Exception
     */
    private function setProduct(
        Product $entity,
        string $prefix,
        string $relationPrefix,
        Website $website,
        array $entityToImport = []
    ): void
    {
        $features = $this->coreLocator->em()->getRepository(Feature::class)->findAll();
        if (empty($features)) {
            $featuresToImport = $this->sqlService->findAll($prefix.'_module_catalog_feature');
            foreach ($featuresToImport as $featureToImport) {
                $feature = new Feature();
                $this->setProperties($feature, $website, $featureToImport, null, 'module_catalog_feature');
                $valuesToImport = $this->sqlService->findBy($prefix.'_module_catalog_feature_value', 'catalogfeature_id', $featureToImport['id'], 'position', 'ASC');
                foreach ($valuesToImport as $valueToImport) {
                    $featureValue = new FeatureValue();
                    $this->setProperties($featureValue, $website, $valueToImport, null, 'module_catalog_feature_value');
                    $feature->addValue($featureValue);
                }
                $this->coreLocator->em()->persist($feature);
            }
        }

        $categories = $this->coreLocator->em()->getRepository(Category::class)->findAll();
        if (empty($categories)) {
            $categoriesToImport = $this->sqlService->findAll($prefix.'_module_catalog_category');
            foreach ($categoriesToImport as $categoryToImport) {
                $category = new Category();
                $this->setProperties($category, $website, $categoryToImport, null, 'module_catalog_category');
                $this->coreLocator->em()->persist($category);
            }
        }

        $catalogToImport = $this->sqlService->find($prefix.'_module_catalog', 'id', $entityToImport['catalog_id']);
        $catalog = $this->coreLocator->em()->getRepository(Catalog::class)->findOneBy(['slug' => $catalogToImport['slug']]);
        if (!$catalog) {
            $catalog = new Catalog();
            $this->setProperties($catalog, $website, $catalogToImport, null, 'module_catalog');
            $this->coreLocator->em()->persist($catalog);
        }
        $entity->setCatalog($catalog);

        foreach ($entity->getValues() as $value) {
            $entity->removeValue($value);
        }
        if ($entity->getValues()->isEmpty()) {
            $valuesToImport = $this->sqlService->findBy($prefix.'_module_catalog_product_values', 'product_id', $entityToImport['id']);
            foreach ($valuesToImport as $key => $valueToImport) {
                $parentValueToImport = $this->sqlService->find($prefix.'_module_catalog_feature_value', 'id', $valueToImport['value_id']);
                $featureToImport = $this->sqlService->find($prefix.'_module_catalog_feature', 'id', $parentValueToImport['catalogfeature_id']);
                $feature = $this->coreLocator->em()->getRepository(Feature::class)->findOneBy(['slug' => $featureToImport['slug']]);
                $value = $this->coreLocator->em()->getRepository(FeatureValue::class)->findOneBy(['slug' => $parentValueToImport['slug'], 'catalogfeature' => $feature->getId()]);
                if ($value) {
                    $valueProduct = new FeatureValueProduct();
                    $valueProduct->setProduct($entity);
                    $valueProduct->setFeature($value->getCatalogfeature());
                    $valueProduct->setValue($value);
                    $this->setProperties($valueProduct, $website, $valueToImport);
                    $valueProduct->setPosition($key + 1);
                    $valueProduct->setFeaturePosition($key + 1);
                    $entity->addValue($valueProduct);
                }
            }
        }

        foreach ($entity->getCategories() as $category) {
            $entity->removeCategory($category);
        }
        $categoriesToImport = $this->sqlService->findBy($prefix.'_module_catalog_product_categories', 'product_id', $entityToImport['id']);
        foreach ($categoriesToImport as $categoryToImport) {
            $categoryToImport = $this->sqlService->find($prefix.'_module_catalog_category', 'id', $categoryToImport['category_id']);
            $category = $this->coreLocator->em()->getRepository(Category::class)->findOneBy(['slug' => $categoryToImport['slug']]);
            if (!$category) {
                $category = new Category();
                $this->setProperties($category, $website, $categoryToImport);
                $this->coreLocator->em()->persist($category);
            }
            if ($category) {
                $entity->addCategory($category);
            }
        }

        foreach ($entity->getProducts() as $product) {
            $entity->removeProduct($product);
        }
        if ($entity->getProducts()->isEmpty()) {
            $productsToImport = $this->sqlService->findBy($prefix.'_module_catalog_product_products', 'product_id', $entityToImport['id']);
            foreach ($productsToImport as $productToImport) {
                $productToImport = $this->sqlService->find($prefix.'_module_catalog_product', 'id', $productToImport['product_child_id']);
                $product = $productToImport ? $this->coreLocator->em()->getRepository(Product::class)->findOneBy(['slug' => $productToImport['slug']]) : null;
                if ($product) {
                    $entity->addProduct($product);
                }
            }
        }
    }

    /**
     * To set Form.
     *
     * @throws \Exception
     */
    private function setForm(
        Form $form,
        string $prefix,
        string $relationPrefix,
        Website $website,
        array $entityToImport = []
    ): void
    {
        $configurationToImport = $this->sqlService->find($prefix.'_module_form_configuration', 'form_id', $entityToImport['id']);
        if (!$form->getConfiguration()) {
            $configuration = new Configuration();
            $this->setProperties($configuration, $website, $configurationToImport, null, 'module_form_configuration');
            $form->setConfiguration($configuration);
        }
    }

    /**
     * To set Menu.
     *
     * @throws \Exception
     */
    private function setMenu(
        Menu $menu,
        string $prefix,
        string $relationPrefix,
        Website $website,
        array $entityToImport = []
    ): void
    {
        $linksToImport = $this->sqlService->findBY($prefix.'_module_menu_link', 'menu_id', $entityToImport['id']);
        foreach ($linksToImport as $linkToImport) {
            if ($linkToImport['level'] === 1) {
                $link = $this->coreLocator->em()->getRepository(Link::class)->findOneBy(['slug' => $linkToImport['slug'], 'menu' => $menu]);
                if (!$link) {
                    $this->addLink($menu, $website, $linkToImport, $prefix);
                }
            }
        }
    }

    /**
     * To add Link
     *
     * @throws \Exception
     */
    private function addLink(Menu $menu, Website $website, array $linkToImport, string $prefix): Link
    {
        $link = new Link();
        $link->setMenu($menu);
        $this->setProperties($link, $website, $linkToImport, null, 'module_menu_link');
        $link->setPosition($linkToImport['position']);
        $link->setLevel($linkToImport['level']);
        $menu->addLink($link);
        $linksToImport = $this->sqlService->findBY($prefix.'_module_menu_link', 'parent_id', $linkToImport['id']);
        foreach ($linksToImport as $subLinkToImport) {
            if ($linkToImport['menu_id'] === $subLinkToImport['menu_id']) {
                $subLink = $this->addLink($menu, $website, $subLinkToImport, $prefix);
                $subLink->setParent($link);
            }
        }
        return $link;
    }

    /**
     * To set Faq.
     *
     * @throws \Exception
     */
    private function setFaq(
        Faq $faq,
        string $prefix,
        string $relationPrefix,
        Website $website,
        array $entityToImport = []
    ): void
    {
        if ($faq->getQuestions()->isEmpty()) {
            $questionsToImport = $this->sqlService->findBY($prefix.'_module_faq_question', 'faq_id', $entityToImport['id']);
            foreach ($questionsToImport as $questionToImport) {
                $question = new Question();
                $this->setProperties($question, $website, $questionToImport, $questionToImport['position'], 'module_faq_question');
                $question->setFaq($faq);
                $question->setPosition($questionToImport['position']);
                $faq->addQuestion($question);
            }
        }
    }
}
