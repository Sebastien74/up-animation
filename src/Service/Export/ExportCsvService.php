<?php

declare(strict_types=1);

namespace App\Service\Export;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use ForceUTF8\Encoding;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Yaml\Yaml;

/**
 * ExportCsvService.
 *
 * To generate export CSV
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ExportCsvService::class, 'key' => 'core_export_service'],
])]
class ExportCsvService
{
    private array $alphas = [];
    private array $yamlInfos = [];
    private Worksheet $sheet;
    private int $headerAlphaIndex = 0;
    private int $entityAlphaIndex = 0;

    /**
     * ExportCsvService constructor.
     */
    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * To execute service.
     */
    public function execute(array $entities, array $interface): array
    {
        $this->alphas();

        $referEntity = new $interface['classname']();
        $configuration = !empty($interface['configuration']) ? $interface['configuration'] : null;
        $exportFields = $configuration ? $configuration->exports : [];
        $spreadsheet = new Spreadsheet();

        try {
            $this->sheet = $spreadsheet->getActiveSheet();
        } catch (\Exception $exception) {
            $session = new Session();
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        $this->yamlInfos($interface);
        $this->header($referEntity, $exportFields);
        $this->body($referEntity, $entities, $exportFields);

        $filename = $interface['name'].'.xlsx';
        $tempFile = $this->projectDir.'/bin/export/'.$filename;
        $tempFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tempFile);
        $writer = new Xlsx($spreadsheet);

        try {
            $writer->save($tempFile);
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $exception) {
            $session = new Session();
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return [
            'tempFile' => $tempFile,
            'fileName' => $filename,
        ];
    }

    /**
     * Set alphas keys.
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
     * Set Yaml entity infos.
     */
    private function yamlInfos(array $interface = []): void
    {
        $interfaceName = !empty($interface['name']) ? $interface['name'] : null;
        if ($interfaceName) {
            $fileDirname = $this->projectDir.'/bin/data/export/'.$interfaceName.'.yaml';
            $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);
            $filesystem = new Filesystem();
            if ($filesystem->exists($fileDirname)) {
                $this->yamlInfos = Yaml::parseFile($fileDirname);
            }
        }
    }

    /**
     * Set Header.
     */
    private function header($referEntity, array $exportFields = []): void
    {
        foreach ($exportFields as $fieldName) {
            $getter = 'get'.ucfirst($fieldName);
            if (method_exists($referEntity, $getter) && !$referEntity->$getter() instanceof PersistentCollection && !$referEntity->$getter() instanceof ArrayCollection || preg_match('/./', $fieldName)) {
                $name = !empty($this->yamlInfos['columns'][$fieldName]) ? $this->yamlInfos['columns'][$fieldName] : $fieldName;
                $this->sheet->setCellValue($this->alphas[$this->headerAlphaIndex]. 1, Encoding::fixUTF8($name));
                $this->sheet->getColumnDimension($this->alphas[$this->headerAlphaIndex])->setAutoSize(true);
                ++$this->headerAlphaIndex;
            }
        }
    }

    /**
     * Set Body.
     */
    private function body($referEntity, array $entities = [], array $exportFields = []): void
    {
        $indexEntity = 2;

        foreach ($entities as $entity) {
            $this->entityAlphaIndex = 0;

            foreach ($exportFields as $fieldName) {
                $getter = 'get'.ucfirst($fieldName);

                if (method_exists($entity, $getter) && $entity->$getter() instanceof \DateTime) {
                    $value = $entity->$getter()->format('Y-m-d H:i:s');
                    $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$indexEntity, Encoding::fixUTF8($value));
                    ++$this->entityAlphaIndex;
                } elseif (method_exists($entity, $getter) && !$entity->$getter() instanceof PersistentCollection && !$referEntity->$getter() instanceof ArrayCollection) {
                    $value = !empty($this->yamlInfos['values'][$entity->$getter()]) ? $this->yamlInfos['values'][$entity->$getter()] : null;
                    $value = $value ?: $entity->$getter();
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $value = $value && !is_string($value) && !is_numeric($value) ? null : $value;
                    $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$indexEntity, Encoding::fixUTF8($value));
                    ++$this->entityAlphaIndex;
                } elseif (preg_match('/./', $fieldName)) {
                    $associationsFields = explode('.', $fieldName);
                    $collectionValues = '';
                    $associationValue = $entity;
                    foreach ($associationsFields as $associationFieldName) {
                        $getter = 'get'.ucfirst($associationFieldName);
                        if (method_exists($associationValue, $getter) && !$associationValue->$getter() instanceof PersistentCollection) {
                            $associationValue = $associationValue->$getter();
                        } elseif (method_exists($associationValue, $getter) && $associationValue->$getter() instanceof PersistentCollection) {
                            foreach ($associationValue->$getter() as $collectionValue) {
                                foreach ($associationsFields as $collectionAssociationFieldName) {
                                    $collectionGetter = 'get'.ucfirst($collectionAssociationFieldName);
                                    if (method_exists($collectionValue, $collectionGetter) && !$collectionValue->$collectionGetter() instanceof PersistentCollection) {
                                        $collectionValue = $collectionValue->$collectionGetter();
                                        if ($collectionValue instanceof \DateTime) {
                                            $collectionValue = $collectionValue->format('Y-m-d H:i:s');
                                        }
                                        $collectionValues .= $collectionValue.' '.PHP_EOL;
                                    }
                                }
                            }
                            $associationValue = $collectionValues;
                        }
                    }
                    if ($associationValue instanceof \DateTime) {
                        $associationValue = $associationValue->format('Y-m-d H:i:s');
                    }
                    $associationValue = is_string($associationValue) ? Encoding::fixUTF8($associationValue) : null;

                    $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$indexEntity, $associationValue);
                    ++$this->entityAlphaIndex;
                }
            }

            ++$indexEntity;
        }
    }
}
