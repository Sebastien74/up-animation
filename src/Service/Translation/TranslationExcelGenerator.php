<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Entity\Core\Website;
use App\Entity\Translation\Translation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel generator for translation exports.
 */
class TranslationExcelGenerator
{
    /**
     * Generate XLSX files for internationalized entities.
     */
    public function generateIntlFiles(array $fileData, Website $website, string $exportDir): void
    {
        foreach ($fileData as $tableName => $locales) {
            foreach ($locales as $locale => $entities) {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $sheet->setCellValue($this->getCsvIntlsIndex('locale', $tableName) . '1', 'locale');
                $sheet->setCellValue($this->getCsvIntlsIndex('website', $tableName) . '1', 'website');

                $intlFields = !empty($entities[0]) ? $entities[0]['intlFields'] : [];
                $row = 2;
                foreach ($entities as $entity) {
                    $sheet->setCellValue($this->getCsvIntlsIndex('locale', $tableName) . $row, $locale);
                    $sheet->setCellValue($this->getCsvIntlsIndex('website', $tableName) . $row, $website->getId());
                    foreach ($intlFields as $field) {
                        $colIndex = $this->getCsvIntlsIndex($field->field, $tableName);
                        if (!empty($colIndex)) {
                            if ($row === 2) {
                                $sheet->setCellValue($colIndex . '1', $field->field);
                            }
                            $sheet->setCellValue($colIndex . $row, $this->normalizeAndDecode($entity[$field->field]));
                        }
                    }
                    $row++;
                }

                if (count($entities) < 500) {
                    $this->autoSizeColumns($sheet, $tableName, $intlFields);
                }

                $this->saveSpreadsheet($spreadsheet, $exportDir . '/' . $tableName . '-' . $locale . '.xlsx');
            }
        }
    }

    /**
     * Generate XLSX files for translations.
     */
    public function generateTranslationFiles(array $translations, string $defaultLocale, string $exportDir): void
    {
        foreach ($translations as $locale => $localeTranslation) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'locale');
            $sheet->setCellValue('B1', 'domain');
            $sheet->setCellValue('C1', 'id');
            $sheet->setCellValue('D1', 'content');
            $sheet->setCellValue('E1', 'translation');

            $row = 2;
            foreach ($localeTranslation as $translation) {
                /** @var Translation $translation */
                $defaultContent = null;
                foreach ($translation->getUnit()->getTranslations() as $unitTranslation) {
                    if ($unitTranslation->getLocale() === $defaultLocale) {
                        $defaultContent = $this->normalizeAndDecode($unitTranslation->getContent());
                        break;
                    }
                }
                if ($defaultContent) {
                    $sheet->setCellValue('A' . $row, $translation->getLocale());
                    $sheet->setCellValue('B' . $row, $translation->getUnit()->getDomain()->getName());
                    $sheet->setCellValue('C' . $row, $translation->getId());
                    $sheet->setCellValue('D' . $row, $defaultContent);
                    $sheet->setCellValue('E' . $row, '');
                    $row++;
                }
            }

            $this->saveSpreadsheet($spreadsheet, $exportDir . '/translations-' . $locale . '.xlsx');
        }
    }

    private function autoSizeColumns($sheet, string $tableName, array $intlFields): void
    {
        foreach (['locale', 'website'] as $col) {
            $sheet->getColumnDimension($this->getCsvIntlsIndex($col, $tableName))->setAutoSize(true);
        }
        foreach ($intlFields as $field) {
            $colIndex = $this->getCsvIntlsIndex($field->field, $tableName);
            if ($colIndex) {
                $sheet->getColumnDimension($colIndex)->setAutoSize(true);
            }
        }
    }

    private function saveSpreadsheet(Spreadsheet $spreadsheet, string $path): void
    {
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }

    private function getCsvIntlsIndex(string $column, string $tableName): ?string
    {
        $tableName = str_replace(($_ENV['DATABASE_PREFIX'] ?? '') . '_', '', $tableName);

        $indexes = [
            'locale' => 'A', 'website' => 'B', 'id' => 'C', 'title' => 'D', 'subTitle' => 'E',
            'introduction' => 'F', 'body' => 'G', 'targetLink' => 'H', 'targetLabel' => 'I',
            'placeholder' => 'J', 'help' => 'K', 'error' => 'L',
        ];

        if ('seo' === $tableName) {
            $indexes = [
                'locale' => 'A', 'website' => 'B', 'id' => 'C', 'metaTitle' => 'D', 'metaTitleSecond' => 'E',
                'breadcrumbTitle' => 'F', 'metaDescription' => 'G', 'keywords' => 'H', 'author' => 'I',
                'authorType' => 'J', 'footerDescription' => 'K', 'metaCanonical' => 'L', 'metaOgTitle' => 'M',
                'metaOgDescription' => 'N',
            ];
        } elseif ('seo_url' === $tableName) {
            $indexes = ['locale' => 'A', 'website' => 'B', 'id' => 'C', 'code' => 'D'];
        }

        return $indexes[$column] ?? null;
    }

    private function normalizeAndDecode($value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'normalizeAndDecode'], $value);
        }
        if (is_int($value) || is_numeric($value) || is_bool($value) || $value === null || $value === '') {
            return $value;
        }
        if (!is_string($value)) {
            return $value;
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $enc = mb_detect_encoding($value, ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15', 'UTF-8'], true) ?: 'Windows-1252';
            $converted = @iconv($enc, 'UTF-8//IGNORE', $value);
            if ($converted === false) {
                $converted = @mb_convert_encoding($value, 'UTF-8', $enc);
            }
            $value = $converted;
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        for ($i = 0; $i < 3; $i++) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $value) break;
            $value = $decoded;
        }

        return $value;
    }
}
