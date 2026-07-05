<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Entity\Layout\Layout;
use App\Entity\Module\Catalog;
use App\Model\BaseModel;
use App\Model\Core\WebsiteModel;
use App\Model\EntityModel;
use App\Model\InformationModel;
use App\Model\ViewModel;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * ProductModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ProductModel extends BaseModel
{
    private const int MEDIA_CARD_LIMIT = 1;
    private static array $cache = [];

    /**
     * fromEntity.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|ReflectionException|QueryException
     */
    public static function fromEntity(Catalog\Product $product, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        $onlyForUrl = !empty($options['onlyForUrl']);
        if ($onlyForUrl) {
            // Mirror CatalogModel: skip everything irrelevant to a link, but keep info
            // (city/zipcode) and intl, which menu/footer renders of these models still use.
            $options = array_merge([
                'disabledValues' => true,
                'disabledProducts' => true,
                'disabledLayout' => true,
                'disabledMedias' => true,
                'disabledCategories' => true,
                'disabledCategory' => true,
                'disabledSubCategories' => true,
                'disabledAgency' => true,
            ], $options);
        }

        $website = self::$coreLocator->website();
        $catalogDb = self::getContent('catalog', $product);

        // Resolving a product link only needs the ViewModel (url, intl): skip the catalog-wide
        // batch loads and the per-product collections that would otherwise trigger an N+1.
        if (!$onlyForUrl) {
            self::$cache['allProducts'] = array_key_exists('allProducts', self::$cache) ? self::$cache['allProducts']
                : self::$coreLocator->em()->getRepository(Catalog\Product::class)->findOnlineByCatalogs($website->entity, self::$coreLocator->locale(), [$catalogDb]);
            self::$cache['allValues'] = array_key_exists('allValues', self::$cache) ? self::$cache['allValues']
                : self::$coreLocator->em()->getRepository(Catalog\FeatureValueProduct::class)->findByProductIds(self::$cache['allProducts']);
        }

        $disabledLayout = isset($options['disabledLayout']) && $options['disabledLayout'];
        $disabledAgency = isset($options['disabledAgency']) && $options['disabledAgency'];
        $model = ViewModel::fromEntity($product, $coreLocator, array_merge($options, []));
        $catalog = ViewModel::fromEntity($catalogDb, $coreLocator, array_merge($options, []));
        $catalogSlug = self::getContent('slug', $catalog);
        $layoutCache = array_key_exists('catalogLayout', self::$cache) ? self::$cache['catalogLayout'] : [];
        $catalogLayout = self::$cache['catalogLayout'][$catalog->id] = array_key_exists($catalog->id, $layoutCache)
            ? $layoutCache[$catalog->id] : self::getContent('layout', $catalog->entity);
        $disabledValues = array_key_exists('disabledValues', $options) ? $options['disabledValues'] : false;
        $disabledInfo = array_key_exists('disabledInfo', $options) ? $options['disabledInfo'] : false;

        if (isset($options['entitiesIds'])) {
            unset($options['entitiesIds']);
        }

        $defaultUniqSubCategories = self::getConfig($catalogSlug, 'defaultUniqSubCategories');
        $multiFeaturesValues = self::getConfig($catalogSlug, 'multiFeaturesValues');
        $defaultUniqFeatures = self::getConfig($catalogSlug, 'defaultUniqFeatures');

        $values['defaults'] = [];
        $values = !$disabledValues ? self::getValues($product, $catalogDb, $multiFeaturesValues, $defaultUniqFeatures, $options) : $values;
        $subCategories = $onlyForUrl || !empty($options['disabledSubCategories']) ? [] : self::getSubCategories($product, $options, $defaultUniqSubCategories);

        $disabledProducts = isset($options['disabledProducts']) && $options['disabledProducts'];
        $products = [];
        if (!$disabledProducts) {
            foreach ($product->getProducts() as $associatedProduct) {
                $products[] = ProductModel::fromEntity($associatedProduct, self::$coreLocator, ['disabledProducts' => true]);
            }
        }

        $information = !$disabledInfo && in_array('informations', $catalogDb->getTabs()) ? self::information($product) : false;
        $address = $information ? $information->address : false;
        $mainPages = $website->configuration->pages;
        $contactPageUrl = !empty($mainPages['contact']) && $mainPages['contact']->code ? $mainPages['contact']->code : false;
        $contactPageParams = $contactPageUrl ? ['url' => $contactPageUrl, 'agence' => $model->slug] : [];
        $displayCity = !$onlyForUrl && ((array_key_exists('displayCity', $options) && $options['displayCity']) || self::$coreLocator->request()->attributes->get('agency'));
        $agencyQuery = self::$coreLocator->request()->attributes->get('agency') ? self::$coreLocator->request()->attributes->get('agency') : self::$coreLocator->request()->attributes->get('url');
        $agencyDb = $displayCity && $agencyQuery ? self::$coreLocator->em()->getRepository(Catalog\Product::class)->findByUrlAndLocale($agencyQuery, $website->entity, self::$coreLocator->locale())
            : false;
        self::$cache['agency'] = $agencyDb && array_key_exists('agency', self::$cache) ? self::$cache['agency']
            : ($agencyDb && !$disabledAgency ? ProductModel::fromEntity($agencyDb, self::$coreLocator, ['disabledAgency' => true]) : false);

        $color = self::getConfig($catalogSlug, 'color');
        $icon = self::getConfig($catalogSlug, 'icon');

        return (object) array_merge((array) $model, [
            'catalog' => $catalog,
            'promote' => self::getContent('promote', $product, true),
            'reference' => self::getContent('reference', $product),
            'asAgency' => 'agencies' === self::getContent('slug', $catalog),
            'displayCity' => $displayCity,
            'agency' => self::$cache['agency'],
            'catalogSlug' => $catalogSlug,
            'color' => $color,
            'icon' => $icon,
            'entityForLayout' => $model->layout && $model->layout->getSlug() && !$model->layout->getZones()->isEmpty() && $model->asCustomLayout ? $model->entity : $catalog,
            'info' => $information,
            'address' => $address,
            'city' => $address ? $address['city'] : false,
            'department' => $address && 'FR' === $address['country'] ? $address['department'] : false,
            'region' => $address && 'FR' === $address['country'] ? $address['region'] : false,
            'zipcode' => $address && 'FR' === $address['country'] ? $address['zipCode'] : false,
            'country' => $address ? $address['country'] : false,
            'zipcodeSmall' => $address && $address['zipCode'] && 'FR' === $address['country'] ? '('.substr($address['zipCode'], 0 , 2).')' : false,
            'subCategories' => $subCategories,
            'mediasCard' => $model->medias ? array_slice($model->medias, array_key_first($model->medias), self::MEDIA_CARD_LIMIT) : [],
            'values' => $values,
            'products' => $products,
            'template' => $model->layout ? self::getTemplate($model, $catalog->entity, $catalogLayout) : false,
            'haveLayout' => !$disabledLayout && $model->haveLayout ? $model->haveLayout : !$disabledLayout && $catalogLayout && !$catalogLayout->getZones()->isEmpty(),
            'asCustomLayout' => !$disabledLayout && $model->haveLayout ? $model->haveLayout : !$disabledLayout && $catalogLayout && !$catalogLayout->getZones()->isEmpty(),
            'faq' => $product->getFaq() ? $product->getFaq()->getId() : null,
            'mainFeature' => self::mainFeature($catalogDb, $values),
            'formPageUrl' => self::getFormPage($model),
            'indexUrl' => $model->urlIndex && $model->urlCode ? self::$coreLocator->router()->generate('front_index', ['url' => $model->urlIndex], UrlGeneratorInterface::ABSOLUTE_URL) : null,
            'contactUrl' => $contactPageUrl ? self::$coreLocator->router()->generate('front_index', $contactPageParams, UrlGeneratorInterface::ABSOLUTE_URL) : self::$coreLocator->router()->generate('front_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ], $values['defaults'], $subCategories);
    }

    /**
     * Get configuration by catalog slug and type.
     */
    private static function getConfig(string $catalogSlug, string $type): array|string
    {
        return match ($type) {
            'color' => match ($catalogSlug) {
                'agencies' => 'primary',
                'events' => 'info-light',
                'services' => 'info',
                'rentals' => 'warning',
                default => 'white',
            },
            'icon' => match ($catalogSlug) {
                'agencies' => 'headset',
                'events' => 'award',
                'services' => 'podium-star',
                'rentals' => 'boxes',
                default => '',
            },
            'defaultUniqSubCategories' => match ($catalogSlug) {
                'agencies' => [],
                'events' => [],
                'services' => [],
                'rentals' => [],
                default => [],
            },
            'multiFeaturesValues' => match ($catalogSlug) {
                'agencies' => [],
                'events' => ['people', 'duration', 'age', 'environment'],
                'services' => ['people', 'duration', 'age', 'environment'],
                'rentals' => ['people', 'duration', 'age', 'environment'],
                default => [],
            },
            'defaultUniqFeatures' => match ($catalogSlug) {
                'agencies' => [],
                'events' => [],
                'services' => [],
                'rentals' => [],
                default => [],
            },
            default => [],
        };
    }

    /**
     * To get template.
     */
    private static function getTemplate(ViewModel $model, Catalog\Catalog $catalog, ?Layout $catalogLayout = null): string
    {
        $website = self::$coreLocator->website() ? self::$coreLocator->website() : WebsiteModel::fromEntity($catalog->getWebsite(), self::$coreLocator);
        $websiteTemplate = $website->configuration->template;
        $template = $model->haveLayout || $catalogLayout && !$catalogLayout->getZones()->isEmpty() ? 'front/'.$websiteTemplate.'/actions/catalog/view/layout.html.twig' : null;
        $template = $template && self::$coreLocator->fileExist($template) ? $template : 'front/'.$websiteTemplate.'/actions/catalog/view/default-product.html.twig';
        $templateCatalog = 'front/'.$websiteTemplate.'/actions/catalog/view/'.$catalog->getSlug().'.html.twig';

        return self::$coreLocator->fileExist($templateCatalog) ? $templateCatalog : $template;
    }

    /**
     * To get subcategories.
     */
    private static function getSubCategories(Catalog\Product $product, array $options, array $defaultUniqSubCategories = []): array
    {


        $website = self::$coreLocator->website() ? self::$coreLocator->website() : WebsiteModel::fromEntity($product->getWebsite(), self::$coreLocator);

        if (!isset($options['disabledSubCategories']) && !isset(self::$cache['subCategories'])) {
            $subCategories = self::$coreLocator->em()->getRepository(Catalog\SubCategory::class)->findByWebsite($website->entity);
            foreach ($subCategories as $subCategory) {
                self::$cache['subCategories'][$subCategory->getId()] = self::$cache['subCategories'][$subCategory->getId()] ?? EntityModel::fromEntity($subCategory, self::$coreLocator)->response;
            }
        }

        self::$cache['subCategories'] = self::$cache['subCategories'] ?? [];
        $subCategories = [];
        foreach ($product->getSubCategories() as $subCategory) {
            if (!empty(self::$cache['subCategories'][$subCategory->getId()])) {
                $category = $subCategory->getCatalogcategory();
                $subCategories['byCategoriesIds'][$category->getId()] = self::$cache['subCategories'][$subCategory->getId()];
                $subCategories['byCategoriesSlugs'][$category->getSlug()] = self::$cache['subCategories'][$subCategory->getId()];
                $subCategories['byIds'][$subCategory->getId()] = self::$cache['subCategories'][$subCategory->getId()];
                $subCategories['bySlugs'][$subCategory->getSlug()] = self::$cache['subCategories'][$subCategory->getId()];
            }
        }

        return array_map(function ($slug) use ($subCategories) {
            return !empty($subCategories['byCategoriesSlugs'][$slug]) ? $subCategories['byCategoriesSlugs'][$slug] : false;
        }, $defaultUniqSubCategories);
    }

    /**
     * To get values.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|ReflectionException|QueryException
     */
    private static function getValues(Catalog\Product $product, Catalog\Catalog $catalog, array $multiFeaturesValues = [], array $defaultUniqFeatures = [], array $options = []): array
    {

        $website = self::$coreLocator->website() ? self::$coreLocator->website() : WebsiteModel::fromEntity($product->getWebsite(), self::$coreLocator);

        $result = [];
        foreach ($product->getValues() as $value) {
            $valueValue = $value->getValue()?->getSlug();
            $result[$value->getFeature()->getSlug()][] = $valueValue;
        }

        if (!isset(self::$cache['featuresValues'])) {
            self::$cache['featuresValues'] = [];
            self::$coreLocator->em()->getRepository(Catalog\Feature::class)->primeWebsiteEager($website->entity);
            self::$coreLocator->em()->getRepository(Catalog\FeatureValue::class)->primeWebsiteEager($website->entity);
            $features = self::$coreLocator->em()->getRepository(Catalog\Feature::class)->findAllByWebsiteIterate($website);
            foreach ($features as $feature) {
                self::$cache['features'][$feature->getId()] = self::$cache['features'][$feature->getId()] ?? EntityModel::fromEntity($feature, self::$coreLocator)->response;
                foreach ($feature->getValues() as $value) {
                    $featureModel = self::$cache['features'][$feature->getId()];
                    $valueModel = EntityModel::fromEntity($value, self::$coreLocator)->response;
                    self::$cache['featuresValues'][$value->getId()]['entity'] = $value;
                    self::$cache['featuresValues'][$value->getId()]['feature'] = $featureModel;
                    self::$cache['featuresValues'][$value->getId()]['featureTitle'] = $featureModel->intl->title;
                    self::$cache['featuresValues'][$value->getId()]['value'] = $valueModel;
                    self::$cache['featuresValues'][$value->getId()]['valueTitle'] = $valueModel->intl->title;
                    self::$cache['featuresValues'][$value->getId()]['slug'] = $value->getSlug();
                    self::$cache['featuresValues'][$value->getId()]['valueMedia'] = $valueModel->mainMedia;
                }
            }
            ksort(self::$cache['featuresValues']);
        }

        $jsonValues = self::jsonValues($product, $multiFeaturesValues);
        $setJsonValues = false;

        // VOIR POUR METTRE EN ADMIN
        // VOIR POUR METTRE EN ADMIN
        // VOIR POUR METTRE EN ADMIN
        // VOIR POUR METTRE EN ADMIN
        // To add default FeatureValue[]
        self::$cache['valuesCatalog'][$catalog->getId()] = self::$cache['valuesCatalog'][$catalog->getId()] ??
            self::$coreLocator->em()->getRepository(Catalog\FeatureValue::class)->findByCatalog($catalog);
        foreach (self::$cache['valuesCatalog'][$catalog->getId()] as $value) {
            if (empty($jsonValues['byIds'][$value->getId()])) {
                $setJsonValues = true;
                self::addValue($product, $value->getCatalogFeature(), $value);
            }
        }
        // VOIR POUR METTRE EN ADMIN
        // VOIR POUR METTRE EN ADMIN
        // VOIR POUR METTRE EN ADMIN
        // VOIR POUR METTRE EN ADMIN

        // To add default Feature[]
        self::$cache['featuresCatalog'][$catalog->getId()] = self::$cache['featuresCatalog'][$catalog->getId()] ??
            self::$coreLocator->em()->getRepository(Catalog\Feature::class)->findByCatalog($catalog);
        foreach (self::$cache['featuresCatalog'][$catalog->getId()] as $feature) {
            if (empty($jsonValues['featuresByIds'][$feature->getId()])) {
                $setJsonValues = true;
                self::addValue($product, $feature);
            }
        }

        foreach ($product->getValues() as $value) {
            $valueValue = $value->getValue();
            if ($valueValue && empty($jsonValues['byIds'][$valueValue->getId()]) && !empty(self::$cache['featuresValues'][$valueValue->getId()])) {
                self::$cache['byIds'][$valueValue->getId()] = (object) self::$cache['featuresValues'][$valueValue->getId()];
            }
            if ($value->getPosition() !== $value->getFeaturePosition()) {
                $setJsonValues = true;
                $value->setPosition($value->getFeaturePosition());
                self::$coreLocator->em()->persist($value);
            }
        }

        if ($setJsonValues) {
            self::$coreLocator->em()->flush();
            $jsonValues = self::jsonValues($product, $multiFeaturesValues);
        }

        $jsonValues['defaultsUniq'] = self::getUniqFeaturesValues($jsonValues, $defaultUniqFeatures);
        $jsonValues['defaultsMulti'] = !empty($jsonValues['defaultsMulti']) ? $jsonValues['defaultsMulti'] : [];
        $jsonValues['defaults'] = array_merge($jsonValues['defaultsUniq'], $jsonValues['defaultsMulti']);

        return array_merge($jsonValues, $jsonValues['defaultsMulti'], $jsonValues['defaultsUniq']);
    }

    /**
     * Get uniq features values.
     */
    private static function getUniqFeaturesValues(array $values = [], $defaultValues = []): array
    {
        $result = [];
        $values = !empty($values['byIds']) ? $values['byIds'] : [];
        if (!empty(self::$cache['byIds'])) {
            $values = array_merge($values, self::$cache['byIds']);
        }

        foreach ($values as $value) {
            $featureSlug = $value->feature ? $value->feature->slug : '';
            if (in_array($featureSlug, $defaultValues) || isset($defaultValues[$featureSlug])) {
                $featureSlug = $value->feature ? self::stringToCamelCase($featureSlug) : null;
                $defaultSlug = array_search($featureSlug, $defaultValues, true);
                $slug = $defaultSlug ?: $featureSlug;
                $result[$slug] = [
                    'title' => $value->value ? $value->value->intl->title : null,
                    'feature' => $value->feature ?: null,
                    'featureTitle' => $value->feature ? $value->feature->intl->title : null,
                    'value' => $value->value ?: null,
                    'valueTitle' => $value->value ? $value->value->intl->title : null,
                    'valueMedia' => $value->value ? $value->value->mainMedia : null,
                ];
            }
        }

        foreach ($defaultValues as $defaultValue) {
            $featureSlug = self::stringToCamelCase($defaultValue);
            $defaultSlug = array_search($featureSlug, $defaultValues, true);
            $slug = $defaultSlug ?: $featureSlug;
            if (!isset($result[$slug])) {
                $result[$slug] = false;
            }
        }

        return $result;
    }

    /**
     * Add Value.
     */
    private static function addValue(Catalog\Product $product, Catalog\Feature $feature, ?Catalog\FeatureValue $value = null): void
    {
        $jsonData = $product->getJsonValues();
        $arguments = $value ? ['feature' => $feature, 'value' => $value] : ['feature' => $feature];
        $valueProduct = self::$coreLocator->em()->getRepository(Catalog\FeatureValueProduct::class)->findOneBy(array_merge(['product' => $product], $arguments));

        if (!$valueProduct) {
            $valueProduct = new Catalog\FeatureValueProduct();
            $valueProduct->setFeature($feature);
            $valueProduct->setValue($value);
            $valueProduct->setAsDefault(true);
            $valueProduct->setPosition(count($product->getValues()) + 1);
            $valueProduct->setFeaturePosition(count($product->getValues()) + 1);
            $product->addValue($valueProduct);
        }

        $jsonData[$valueProduct->getPosition()] = [
            'feature' => $valueProduct->getFeature()?->getId(),
            'value' => $valueProduct->getValue()?->getId(),
            'displayInArray' => $valueProduct->isDisplayInArray(),
            'position' => $valueProduct->getPosition(),
        ];
        $product->setJsonValues($jsonData);
        self::$coreLocator->em()->persist($product);
    }

    /**
     * Get jsonValues.
     */
    private static function jsonValues(Catalog\Product $product, array $multiFeaturesValues = []): array
    {
        $jsonValues = $product->getJsonValues();
        self::$cache['featuresValues'] = self::$cache['featuresValues'] ?? [];
        $response = [];
        foreach ($jsonValues as $jsonValue) {
            $jsonValue = (object) $jsonValue;
            $valueKey = $jsonValue->value ?? '';
            $featureKey = $jsonValue->feature ?? '';
            $value = !empty(self::$cache['featuresValues'][$valueKey]) ? self::$cache['featuresValues'][$valueKey] : null;
            $feature = !empty(self::$cache['features'][$featureKey]) ? self::$cache['features'][$featureKey] : null;
            if ($value) {
                $value = (object) $value;
                $response['byIds'][$value->value->id] = $value;
                $response['byPositions'][$value->value->position.'-'.$value->feature->slug] = $value;
                $response['bySlugs'][$value->value->slug.'-'.$value->feature->slug] = $value;
                $response['byFeaturesSlugs'][$value->feature->slug][$jsonValue->position] = $value;
                $response['byFeaturesIds'][$value->feature->id][$jsonValue->position] = $value;
                ksort($response['byIds']);
                ksort($response['byPositions']);
                ksort($response['bySlugs']);
                ksort($response['byFeaturesSlugs']);
                ksort($response['byFeaturesSlugs'][$value->feature->slug]);
                ksort($response['byFeaturesIds'][$value->feature->id]);
            }
            if ($feature) {
                $response['featuresByIds'][$feature->id] = $feature;
                ksort($response['featuresByIds']);
            }
        }

        foreach ($multiFeaturesValues as $dbSlug => $slug) {
            $dbSlug = is_string($dbSlug) ? $dbSlug : $slug;
            $response = self::values($response, $dbSlug, $slug);
            $response['defaultsMulti'][$slug] = !empty($response[$slug]) ? $response[$slug] : [];
        }

        return $response;
    }

    /**
     * Set values.
     */
    private static function values(array $values, string $dbSlug, string $slug): array
    {
        if (!empty($values['byFeaturesSlugs'][$dbSlug])) {
            foreach ($values['byFeaturesSlugs'][$dbSlug] as $speaker) {
                $slugValue = Urlizer::urlize($speaker->value->intl->title);
                $values[$slug][substr($slugValue, 0,40)] = $speaker;
            }
            ksort($values[$slug]);
        }

        return $values;
    }

    /**
     * Get information.
     *
     * @throws ReflectionException
     */
    private static function information(Catalog\Product $product): ?InformationModel
    {
        self::$cache['infosEntity'][$product->getId()] = self::$cache['infosEntity'][$product->getId()] ?? $product->getInformation();
        self::$cache['infos'][$product->getId()] = self::$cache['infosEntity'][$product->getId()];
        self::$cache['infos'][$product->getId()] = self::jsonCache(self::$cache['infos'][$product->getId()], self::$coreLocator->locale(), InformationModel::class);
        return self::$cache['infos'][$product->getId()];
    }

    /**
     * Get main Feature.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function mainFeature(Catalog\Catalog $catalog, array $values = []): ?object
    {
        $feature = null;
        $catalogSlug = self::getContent('slug', $catalog);

//        if ('my-catalog-name' === $catalogSlug) {
//            $features = !empty($values['byFeaturesSlugs']['my-feature-slug']) ? $values['byFeaturesSlugs']['my-feature-slug'] : [];
//            $firstKey = array_key_first($features);
//            $feature = $firstKey && !empty($features[$firstKey]) ? $features[$firstKey]->value : null;
//        }

        return $feature;
    }
}
