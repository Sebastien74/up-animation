<?php

declare(strict_types=1);

namespace App\Service\Core;

use ForceUTF8\Encoding;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * XlsxFileReader.
 *
 * Read Xlsx file
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class XlsxFileReader
{
    private array $mapping = [];
    private array $iterations = [];

    /**
     * Read UploadedFile.
     *
     * @throws Exception
     */
    public function read(UploadedFile $tmpFile, bool $formatColumnNames = false, bool $onlyFirstTab = false, bool $simpleFormatter = false): object
    {
        $this->mapping = [];
        $this->iterations = [];

        $inputFileType = ucfirst($tmpFile->getClientOriginalExtension());
        $reader = IOFactory::createReader($inputFileType);
        $spreadsheet = $reader->load($tmpFile->getRealPath());

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $tabTitle = $this->nameFormatter($worksheet->getTitle(), '_', false, $simpleFormatter);
            $this->getMapping($worksheet, $tabTitle, $formatColumnNames, $simpleFormatter);
            $this->getIterations($worksheet, $tabTitle);
        }

        return (object) [
            'mapping' => $onlyFirstTab ? $this->mapping[array_key_first($this->mapping)] : $this->mapping,
            'iterations' => $onlyFirstTab ? $this->iterations[array_key_first($this->iterations)] : $this->iterations,
        ];
    }

    /**
     * Get Header mapping.
     */
    private function getMapping(mixed $worksheet, string $tabTitle, bool $formatColumnNames = false, bool $simpleFormatter = false): void
    {
        foreach ($worksheet->getRowIterator() as $row) {
            $rowIndex = $row->getRowIndex();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $letter => $cell) {
                if (1 === $rowIndex && $cell->getCalculatedValue() && !empty(trim($cell->getCalculatedValue()))) {
                    $this->mapping[$tabTitle][$letter] = $this->nameFormatter($cell->getCalculatedValue(), '_', $formatColumnNames, $simpleFormatter);
                }
            }
        }
    }

    /**
     * Set Xls iteration.
     */
    private function getIterations(mixed $worksheet, string $tabTitle): void
    {
        /* Generate data to parse */
        foreach ($worksheet->getRowIterator() as $row) {
            $rowIndex = $row->getRowIndex();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $letter => $cell) {
                if (!is_null($cell)) {
                    if ($rowIndex > 1 && !empty($this->mapping[$tabTitle][$letter])) {
                        $value = is_numeric($cell->getCalculatedValue()) ? trim(strval($cell->getCalculatedValue())) : $cell->getCalculatedValue();
                        $this->iterations[$tabTitle][$rowIndex][$this->mapping[$tabTitle][$letter]] = $value ? trim($value) : $value;
                    }
                }
            }
        }
    }

    /**
     * Init String without specials chars.
     */
    private function nameFormatter(string $string, string $replacement = '_', bool $formatColumnNames = false, bool $simpleFormatter = false): string
    {
        if ($formatColumnNames) {
            $string = Urlizer::urlize($string);
        }

        if ($string) {
            $string = trim($string);
            $string = Encoding::fixUTF8($string);
            if (!$simpleFormatter) {
                $string = str_replace(['[\', \']'], '', $string);
                $string = preg_replace('/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);/i', '\\1', $string);
                $string = preg_replace('/\[.*\]/U', '', $string);
                $string = htmlentities($string, ENT_COMPAT, 'utf-8');
                $string = htmlentities($string, ENT_IGNORE, 'utf-8');
                $string = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', $replacement, $string);
                $string = preg_replace(['/[^a-z0-9]/i', '/[-]+/'], $replacement, $string);
                $string = str_ends_with($string, '-') ? rtrim($string, $replacement) : $string;
                $string = trim($string);
                $string = str_replace('__', '', $string);
            }
        }

        return $string;
    }
}
