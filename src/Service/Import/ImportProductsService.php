<?php

declare(strict_types=1);

namespace App\Service\Import;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\CategoryIntl;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureIntl;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\FeatureValueIntl;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\ProductIntl;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Service\Core\Urlizer;
use App\Service\Core\XlsxFileReader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * ImportProductsService.
 *
 * To generate export CSV
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ImportProductsService::class, 'key' => 'products_import_service'],
])]
class ImportProductsService
{
    private ?Request $request;
    private array $locales = [];
    private string $locale = 'fr';
    private array $iterations = [];

    /**
     * ImportProductsService constructor.
     */
    public function __construct(
        private readonly XlsxFileReader $fileReader,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * To execute service.
     */
    public function execute($form, Website $website): bool
    {
        /** @var UploadedFile $tmpFile */
        $tmpFile = $form->getData()['files'][0];
        $this->locales = $website->getConfiguration()->getAllLocales();
        $response = $this->fileReader->read($tmpFile, false, true, true);
        if (property_exists($response, 'iterations')) {
            $this->iterations = $response->iterations;
            if (!$this->iterations) {
                return false;
            }
            $this->parse($website);
        }

        return true;
    }

    /**
     * Parse data.
     */
    private function parse(Website $website): void
    {
        foreach ($this->iterations as $key => $row) {

            /* Get and create Product */
            if (!empty($row['id'])) {
                $product = $this->entityManager->getRepository(Product::class)->find($row['id']);
            } else {
                /* Check by adminName */
                $product = null;
                if (array_key_exists('title|fr', $row)) {
                    $product = $this->entityManager->getRepository(Product::class)->findOneBy(['adminName' => $row['title|fr']]);
                }

                if (!$product) {
                    /* Add if not existing */
                    $product = new Product();
                    $catalog = $this->entityManager->getRepository(Catalog::class)->find($this->request->get('catalog'));
                    $product->setCatalog($catalog);
                    $product->setWebsite($website);
                    $product->setAdminName($row['title|fr'] ?? '');
                    $product->setPosition(count($catalog->getProducts()));
                }
            }

            /* Intl */
            foreach ($this->locales as $locale) {
                $intl = $this->getIntlValue($product, $locale);
                if (!$intl) {
                    $intl = new ProductIntl();
                    $intl->setWebsite($website);
                    $intl->setLocale($locale);
                    $product->addIntl($intl);
                }

                if (array_key_exists('title|'.$locale, $row)) {
                    if ('fr' == $locale) {
                        $product->setAdminName($row['title|'.$locale]);
                    }
                    $intl->setTitle($row['title|'.$locale]);
                }

                if (array_key_exists('subTitle|'.$locale, $row)) {
                    $intl->setSubTitle($row['subTitle|'.$locale]);
                }

                if (array_key_exists('introduction|'.$locale, $row)) {
                    $intl->setIntroduction($row['introduction|'.$locale]);
                }

                if (array_key_exists('introduction|'.$locale, $row)) {
                    $intl->setBody($row['description|'.$locale]);
                }

                $this->entityManager->persist($intl);
            }

            /* URL / SEO */
            foreach ($this->locales as $locale) {

                $url = $this->getIntlUrl($product, $locale);

                if (!$url) {
                    $url = new Url();
                    $url->setWebsite($website);
                    $url->setLocale($locale);
                    $product->addUrl($url);
                }

                if (array_key_exists('urlCode|'.$locale, $row)) {
                    $url->setCode($row['urlCode|'.$locale]);
                }

                if (array_key_exists('isOnline|'.$locale, $row)) {
                    $url->setOnline('x' === $row['isOnline|'.$locale]);
                }

                $seo = $url->getSeo();
                if (!$seo) {
                    $seo = new Seo();
                    $url->setSeo($seo);
                    $url->setWebsite($website);
                }

                if (array_key_exists('metaTitle|'.$locale, $row)) {
                    $seo->setMetaTitle($row['metaTitle|'.$locale]);
                    $seo->setMetaOgTitle($row['metaTitle|'.$locale]);
                }

                if (array_key_exists('metaDescription|'.$locale, $row)) {
                    $seo->setMetaDescription($row['metaDescription|'.$locale]);
                    $seo->setMetaOgDescription($row['metaDescription|'.$locale]);
                }

                $this->entityManager->persist($url);
                $this->entityManager->persist($seo);
            }

            /* Categories and associated Product[] */
            if (array_key_exists('mainCategory', $row)) {
                /* First reset the main category */
                $product->setMainCategory(null);
                if (!empty($row['mainCategory'])) {
                    /* Add new if existing */
                    $mainCategoryIntl = $this->entityManager->getRepository(CategoryIntl::class)->findOneBy(['title' => $row['mainCategory'], 'locale' => $this->locale]);
                    if ($mainCategoryIntl) {
                        $product->setMainCategory($mainCategoryIntl->getCategory());
                    }
                }
            }

            if (array_key_exists('categories', $row)) {
                /* First remove the categories */
                foreach ($product->getCategories() as $category) {
                    $product->removeCategory($category);
                }
                if (!empty($row['categories'])) {
                    $allCategories = explode('|', $row['categories']);
                    foreach ($allCategories as $category) {
                        /* Add new if existing */
                        $categoryIntl = $this->entityManager->getRepository(CategoryIntl::class)->findOneBy(['title' => $category, 'locale' => $this->locale]);
                        if ($categoryIntl) {
                            $product->addCategory($categoryIntl->getCategory());
                        }
                    }
                }
            }

            if (array_key_exists('associatedProducts', $row)) {
                /* First remove the associatedProducts */
                foreach ($product->getProducts() as $associatedProduct) {
                    $product->removeProduct($associatedProduct);
                }
                if (!empty($row['associatedProducts'])) {
                    $allProductsAssociated = explode('|', $row['associatedProducts']);
                    foreach ($allProductsAssociated as $productAssociated) {
                        /* Add new if existing */
                        $productAssociatedIntl = $this->entityManager->getRepository(ProductIntl::class)->findOneBy(['title' => $productAssociated, 'locale' => $this->locale]);
                        if ($productAssociatedIntl) {
                            $product->addProduct($productAssociatedIntl->getProduct());
                        }
                    }
                }
            }

            /* Feature values */
            $featureWithValues = [];
            foreach ($row as $key2 => $value) {
                if (str_contains($key2, '::')) {
                    $featureWithValues[$key2] = $value;
                }
            }

            foreach ($featureWithValues as $key2 => $value) {
                $explodedKey = explode('::', $key2);
                $featureTitle = $explodedKey[0];
                $valueTitle = $explodedKey[1];

                $featureIntl = $this->entityManager->getRepository(FeatureIntl::class)->findOneBy(['title' => $featureTitle, 'locale' => $this->locale]);
                $featureValueIntl = $this->entityManager->getRepository(FeatureValueIntl::class)->findOneBy(['title' => $valueTitle, 'locale' => $this->locale]);

                /* Not exist */
                if (!$featureIntl) {
                    $feature = new Feature();
                    $feature->setWebsite($website);
                    $feature->setPosition(count($this->entityManager->getRepository(Feature::class)->findAll()) + 1);
                    $feature->setAdminName($featureTitle);
                    $feature->setSlug(Urlizer::urlize(strip_tags(urldecode($featureTitle))));

                    $featureIntl = new FeatureIntl();
                    $featureIntl->setTitle($featureTitle);
                    $featureIntl->setWebsite($website);
                    $featureIntl->setLocale($this->locale);
                    $feature->addIntl($featureIntl);

                    $this->entityManager->persist($feature);
                    $this->entityManager->persist($featureIntl);
                }

                $featureObj = $featureIntl->getFeature();

                /* Not exist */
                if (!$featureValueIntl) {
                    $featureValue = new FeatureValue();
                    $featureValue->setWebsite($website);
                    $featureValue->setPosition(count($featureObj->getValues()) + 1);
                    $featureValue->setAdminName($valueTitle);
                    $featureValue->setSlug(Urlizer::urlize(strip_tags(urldecode($valueTitle))));

                    $featureValueIntl = new FeatureValueIntl();
                    $featureValueIntl->setTitle($valueTitle);
                    $featureValueIntl->setWebsite($website);
                    $featureValueIntl->setLocale($this->locale);
                    $featureValue->addIntl($featureValueIntl);
                    $featureObj->addValue($featureValue);

                    $this->entityManager->persist($featureValue);
                    $this->entityManager->persist($featureValueIntl);
                }

                $featureValueObj = $featureValueIntl->getFeatureValue();
                $featureValueProduct = $this->entityManager->getRepository(FeatureValueProduct::class)->findOneBy(['product' => $product, 'feature' => $featureObj, 'value' => $featureValueObj]);

                if ('x' === $value) {
                    if (!$featureValueProduct) {
                        /* Add */
                        $featureValueProduct = new FeatureValueProduct();
                        $featureValueProduct->setProduct($product);
                        $featureValueProduct->setFeature($featureObj);
                        $featureValueProduct->setValue($featureValueObj);
                        $featureValueProduct->setSlug($featureValueObj->getSlug());
                        $featureValueProduct->setFeaturePosition(count($product->getValues()) + 1);
                        $featureValueProduct->setPosition(count($product->getValues()) + 1);
                        $this->entityManager->persist($featureValueProduct);
                    }
                } else {
                    /* Remove */
                    if ($featureValueProduct) {
                        $product->removeValue($featureValueProduct);
                        $this->entityManager->remove($featureValueProduct);
                    }
                }
            }

            /* Other */
            if (array_key_exists('isPromote', $row)) {
                $product->setPromote('x' === $row['isPromote']);
            }

            $this->entityManager->flush();
        }
    }

    /**
     * To get locale value.
     */
    private function getIntlValue(mixed $entity, string $locale)
    {
        foreach ($entity->getIntls() as $intl) {
            if ($intl->getLocale() === $locale) {
                return $intl;
            }
        }

        return false;
    }

    /**
     * To get locale url.
     */
    private function getIntlUrl($entity, string $locale)
    {
        foreach ($entity->getUrls() as $url) {
            if ($url->getLocale() === $locale) {
                return $url;
            }
        }

        return false;
    }
}
