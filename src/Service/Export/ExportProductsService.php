<?php

declare(strict_types=1);

namespace App\Service\Export;

use App\Entity\Module\Catalog\Product;
use App\Entity\Seo\Url;
use App\Service\Interface\CoreLocatorInterface;
use ForceUTF8\Encoding;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ExportProductsService.
 *
 * To generate export CSV
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ExportProductsService::class, 'key' => 'products_export_service'],
])]
class ExportProductsService
{
    private array $alphas = [];
    private array $arrayOfFeatures = [];
    private Worksheet $sheet;
    private string $locale = 'fr';
    private array $locales = [];
    private int $entityAlphaIndex = 0;

    /**
     * ExportProductsService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * To execute service.
     */
    public function execute(array $entities, array $interface): array
    {
        $website = $this->coreLocator->website();
        $this->locales = $website->configuration->allLocales;
        $this->alphas();
        $spreadsheet = new Spreadsheet();
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->setHeader($entities);
        $this->setBody($entities);

        $filename = $interface['name'].'.xlsx';
        $tempFile = $this->coreLocator->projectDir().'/bin/export/'.$filename;
        $tempFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tempFile);
        $writer = new Xlsx($spreadsheet);

        try {
            $writer->save($tempFile);
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
        }

        return [
            'tempFile' => $tempFile,
            'fileName' => $filename,
        ];
    }

    /**
     * To set header.
     */
    private function setHeader($entities): void
    {
        $arrayOfFeatures = [];
        foreach ($entities as $entity) {
            foreach ($entity->getValues() as $featureValueProduct) {
                $feature = $featureValueProduct->getFeature();
                $value = $featureValueProduct->getValue();
                if (!isset($arrayOfFeatures[$value->getId()])) {
                    $featureInt = $this->getIntlValue($feature, $this->locale);
                    $valueInt = $this->getIntlValue($value, $this->locale);
                    if ($featureInt && $valueInt) {
                        $arrayOfFeatures[$value->getId()] = [
                            'featureId' => $feature->getId(),
                            'valueId' => $value->getId(),
                            'titleFeature' => $featureInt->getTitle(),
                            'titleValue' => $valueInt->getTitle(),
                        ];
                    }
                }
            }
        }

        uasort($arrayOfFeatures, function ($a, $b) {
            return strcmp($a['titleFeature'], $b['titleFeature']);
        });

        $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'id');
        ++$this->entityAlphaIndex;

        foreach ($this->locales as $locale) {
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'title|'.$locale);
            ++$this->entityAlphaIndex;
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'subTitle|'.$locale);
            ++$this->entityAlphaIndex;
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'introduction|'.$locale);
            ++$this->entityAlphaIndex;
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'description|'.$locale);
            ++$this->entityAlphaIndex;
        }

        $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'mainCategory');
        ++$this->entityAlphaIndex;
        $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'categories');
        ++$this->entityAlphaIndex;
        $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'associatedProducts');
        ++$this->entityAlphaIndex;

        foreach ($this->locales as $locale) {
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'metaTitle|'.$locale);
            ++$this->entityAlphaIndex;
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'metaDescription|'.$locale);
            ++$this->entityAlphaIndex;
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'isOnline|'.$locale);
            ++$this->entityAlphaIndex;
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'urlCode|'.$locale);
            ++$this->entityAlphaIndex;
        }

        $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', 'isPromote');
        ++$this->entityAlphaIndex;

        foreach ($arrayOfFeatures as $index => $featureDatas) {
            $arrayOfFeatures[$index]['letter'] = $this->alphas[$this->entityAlphaIndex];
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].'1', Encoding::fixUTF8($featureDatas['titleFeature']).'::'.Encoding::fixUTF8($featureDatas['titleValue']));
            ++$this->entityAlphaIndex;
        }

        /* Reset to default */
        $this->entityAlphaIndex = 0;
        $this->arrayOfFeatures = $arrayOfFeatures;
    }

    /**
     * To set body.
     */
    private function setBody(iterable $entities): void
    {
        $column = 2;

        /** @var Product $entity */
        foreach ($entities as $entity) {
            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, Encoding::fixUTF8($entity->getId()));
            ++$this->entityAlphaIndex;

            foreach ($this->locales as $locale) {
                $entityIntl = $this->getIntlValue($entity, $locale);
                $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $entityIntl ? Encoding::fixUTF8($entityIntl->getTitle()) : '');
                ++$this->entityAlphaIndex;
                $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $entityIntl ? Encoding::fixUTF8($entityIntl->getSubTitle()) : '');
                ++$this->entityAlphaIndex;
                $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $entityIntl ? Encoding::fixUTF8($entityIntl->getIntroduction()) : '');
                ++$this->entityAlphaIndex;
                $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $entityIntl ? Encoding::fixUTF8($entityIntl->getBody()) : '');
                ++$this->entityAlphaIndex;
            }

            $mainCategory = $entity->getMainCategory();
            $mainCategoryIntl = $this->getIntlValue($mainCategory, $this->locale);

            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $mainCategoryIntl ? Encoding::fixUTF8($mainCategoryIntl->getTitle()) : '');
            ++$this->entityAlphaIndex;

            $categoriesTxt = '';
            foreach ($entity->getCategories() as $category) {
                $categoryIntl = $this->getIntlValue($category, $this->locale);
                if ($categoryIntl) {
                    $categoriesTxt .= $categoryIntl->getTitle().'|';
                }
            }

            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, Encoding::fixUTF8(rtrim($categoriesTxt, '|')));
            ++$this->entityAlphaIndex;

            $associatedProductsTxt = '';
            foreach ($entity->getProducts() as $associatedProduct) {
                $associatedProductIntl = $this->getIntlValue($associatedProduct, $this->locale);
                if ($associatedProductIntl) {
                    $associatedProductsTxt .= $associatedProductIntl->getTitle().'|';
                }
            }

            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, Encoding::fixUTF8(rtrim($associatedProductsTxt, '|')));
            ++$this->entityAlphaIndex;

            foreach ($this->locales as $locale) {
                /** @var Url $url */
                $url = $this->getIntlUrl($entity, $locale);

                $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $url && $url->getSeo() ? Encoding::fixUTF8($url->getSeo()->getMetaTitle()) : '');
                ++$this->entityAlphaIndex;
                $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $url && $url->getSeo() ? Encoding::fixUTF8($url->getSeo()->getMetaDescription()) : '');
                ++$this->entityAlphaIndex;

                $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $url && $url->isOnline() ? 'x' : '');
                ++$this->entityAlphaIndex;
                $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $url && $url->getCode() ? $url->getCode() : '');
                ++$this->entityAlphaIndex;
            }

            $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$column, $entity->isPromote() ? 'x' : '');
            ++$this->entityAlphaIndex;

            foreach ($entity->getValues() as $productFeatureValue) {
                $feature = $productFeatureValue->getFeature();
                $value = $productFeatureValue->getValue();

                if ($feature && $value && isset($this->arrayOfFeatures[$value->getId()])) {
                    $elemArray = $this->arrayOfFeatures[$value->getId()];
                    $this->sheet->setCellValue($elemArray['letter'].$column, 'x');
                }
            }
            ++$column;
            $this->entityAlphaIndex = 0;
        }
    }

    /**
     * To set alphas.
     */
    private function alphas(): void
    {
        $key = 0;
        $alphas = range('A', 'Z');
        foreach ($alphas as $alpha) {
            $this->alphas[$key] = $alpha;
            ++$key;
        }

        $supAlphas = range('A', 'Z');
        foreach ($supAlphas as $supAlpha) {
            foreach ($alphas as $alpha) {
                $this->alphas[$key] = $supAlpha.$alpha;
                ++$key;
            }
        }
    }

    /**
     * To get locale value.
     */
    private function getIntlValue(mixed $entity, string $locale)
    {
        if (!$entity) {
            return false;
        }

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
