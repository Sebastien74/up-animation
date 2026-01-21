<?php

declare(strict_types=1);

namespace App\Service\Export;

use App\Entity\Module\Form;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use ForceUTF8\Encoding;
use libphonenumber\PhoneNumberUtil;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Intl\Countries;

/**
 * ExportContactService.
 *
 * To generate export CSV
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ExportContactService::class, 'key' => 'contacts_export_service'],
])]
class ExportContactService
{
    private array $header = [];
    private array $data = [];
    private array $alphas = [];
    private array $blocksTypes = [];
    private Worksheet $sheet;
    private int $headerAlphaIndex = 0;
    private int $entityAlphaIndex = 0;

    /**
     * ExportContactService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * To execute service.
     */
    public function execute(array $entities, array $interface): array
    {
        $referEntity = new $interface['classname']();
        $configuration = !empty($interface['configuration']) ? $interface['configuration'] : null;
        $exportFields = $configuration ? $configuration->exports : [];
        $spreadsheet = new Spreadsheet();
        $this->alphas = range('A', 'Z');

        try {
            $this->sheet = $spreadsheet->getActiveSheet();
        } catch (\Exception $e) {
        }

        if (($i = array_search('createdAt', $exportFields, true)) !== false) {
            array_splice($exportFields, $i, 1);
            array_unshift($exportFields, 'createdAt');
        }

        $this->header($referEntity, $exportFields);
        $this->body($referEntity, $entities, $exportFields);

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
     * Set Header.
     */
    private function header($referEntity, array $exportFields = []): void
    {
        foreach ($exportFields as $fieldName) {
            $getter = 'get'.ucfirst($fieldName);
            if (method_exists($referEntity, $getter) && !$referEntity->$getter() instanceof PersistentCollection && !$referEntity->$getter() instanceof ArrayCollection) {
                $label = 'createdAt' === $fieldName ? $this->coreLocator->translator()->trans('Créé le', [], 'admin') : $fieldName;
                $this->header[$fieldName] = Encoding::fixUTF8($label);
                $this->sheet->setCellValue($this->alphas[$this->headerAlphaIndex]. 1, Encoding::fixUTF8($label));
                $this->sheet->getColumnDimension($this->alphas[$this->headerAlphaIndex])->setAutoSize(true);
                ++$this->headerAlphaIndex;
            } elseif ($referEntity instanceof Form\ContactForm && 'contactValues' === $fieldName) {
                $this->contactFormHeader();
            }
        }
    }

    /**
     * Set ContactForm Header.
     */
    private function contactFormHeader(): void
    {
        $form = $this->coreLocator->em()->getRepository(Form\Form::class)->find($this->coreLocator->request()->get('form'));
        $zones = $form->getLayout()->getZones();
        $excluded = [SubmitType::class];

        $values = [];
        foreach ($zones as $zone) {
            foreach ($zone->getCols() as $col) {
                foreach ($col->getBlocks() as $block) {
                    if (!in_array($block->getBlockType()->getFieldType(), $excluded)) {
                        $entitled = $this->getIntlEntitled($block);
                        $values[$block->getId()] = $entitled;
                        $this->header[$block->getId()] = $entitled;
                        $this->blocksTypes[$block->getId()] = $block->getBlockType() ? str_replace('form-', '', $block->getBlockType()->getSlug()) : false;
                    }
                }
            }
        }

        ksort($values);

        foreach ($values as $name) {
            $this->sheet->setCellValue($this->alphas[$this->headerAlphaIndex]. 1, Encoding::fixUTF8($name));
            $this->sheet->getColumnDimension($this->alphas[$this->headerAlphaIndex])->setAutoSize(true);
            ++$this->headerAlphaIndex;
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
                if (isset($this->header[$fieldName]) && method_exists($entity, $getter) && !$entity->$getter() instanceof PersistentCollection && !$referEntity->$getter() instanceof ArrayCollection) {
                    $value = $entity->$getter() instanceof \DateTime ? $entity->$getter()->format('Y-m-d') : ($entity->$getter() ? Encoding::fixUTF8($entity->$getter()) : null);
                    $this->data[$entity->getId()][$fieldName] = $value;
                    $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$indexEntity, $value);
                    ++$this->entityAlphaIndex;
                } elseif ($referEntity instanceof Form\ContactForm && 'contactValues' === $fieldName) {
                    $this->contactValues($indexEntity, $entity);
                }
            }
            ++$indexEntity;
        }
    }

    /**
     * Set ContactValues.
     */
    private function contactValues(int $indexEntity, mixed $entity): void
    {
        $excluded = [SubmitType::class];
        $values = [];

        foreach ($entity->getContactValues() as $value) {
            /** @var Form\ContactValue $value */
            $block = $value->getConfiguration() ? $value->getConfiguration()->getBlock() : false;
            if ($block && isset($this->header[$block->getId()]) && !in_array($block->getBlockType()->getFieldType(), $excluded)) {
                $values[$block->getId()] = [
                    'blockId' => $block->getId(),
                    'value' => $value->getValue()
                ];
            }
        }

        // If count of contact values inferior of form fields count
        if (count($values) < count($this->header)) {
            foreach ($this->header as $blockId => $entitled) {
                if (is_numeric($blockId) && !isset($values[$blockId])) {
                    $values[$blockId] = [
                        'blockId' => $blockId,
                        'value' => $this->getUnknownValue($entity, $blockId)
                    ];
                }
            }
        }

        ksort($values);

        foreach ($values as $value) {
            if (isset($this->header[$value['blockId']])) {
                $data = $value['value'] instanceof \DateTime ? $value['value']->format('Y-m-d') : ($value['value'] ? Encoding::fixUTF8($value['value']) : null);
                $this->data[$entity->getId()][$value['blockId']] = $data;
                $this->sheet->setCellValue($this->alphas[$this->entityAlphaIndex].$indexEntity, $data);
                ++$this->entityAlphaIndex;
            }
        }
    }

    /**
     * Get intl entitled.
     */
    private function getIntlEntitled(mixed $entity): ?string
    {
        $getter = 'get'.ucfirst('title');
        $entitled = method_exists($entity, 'getAdminName') && $entity->getAdminName() ? $entity->getAdminName() : null;
        if (method_exists($entity, 'getIntls')) {
            foreach ($entity->getIntls() as $intl) {
                if (method_exists($entity, $getter) && $intl->$getter && $intl->getLocale() === $this->coreLocator->request()->getLocale()) {
                    $entitled = $intl->$getter;
                }
            }
        }

        return $entitled;
    }

    /**
     * Get unknown value.
     */
    private function getUnknownValue(mixed $entity, mixed $blockId): ?string
    {
        $value = null;
        foreach ($entity->getContactValues() as $contactValue) {
            if (!empty($this->blocksTypes[$blockId]) && 'email' === $this->blocksTypes[$blockId]) {
                $isEmail = $contactValue->getValue() && filter_var($contactValue->getValue(), FILTER_VALIDATE_EMAIL);
                if ($isEmail) {
                    $value = $contactValue->getValue();
                    break;
                }
            }
            elseif (!empty($this->blocksTypes[$blockId]) && 'phone' === $this->blocksTypes[$blockId]) {
                foreach (Countries::getNames() as $code => $name) {
                    $phoneUtil = PhoneNumberUtil::getInstance();
                    try {
                        if ($phoneUtil->parse($contactValue->getValue(), strtoupper($code))) {
                            $value = $contactValue->getValue();
                            break;
                        }
                    } catch (\Exception $exception) {
                        continue;
                    }
                }
            }
            elseif (!empty($this->blocksTypes[$blockId]) && 'textarea' === $this->blocksTypes[$blockId]) {
                if ($contactValue->getValue() !== '' && preg_match('/\R/u', $contactValue->getValue())) {
                    $value = $contactValue->getValue();
                    break;
                }
            }
        }

        return $value;
    }
}
