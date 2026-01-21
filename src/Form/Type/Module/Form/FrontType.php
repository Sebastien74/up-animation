<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Module\Form as FormEntities;
use App\Form\Validator;
use App\Form\Widget as WidgetType;
use App\Model\IntlModel;
use App\Model\Layout\BlockModel;
use App\Model\ViewModel;
use App\Service\Core\InterfaceHelper;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Content\FileRuntime;
use App\Twig\Translation\IntlRuntime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FrontType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontType extends AbstractType
{
    private const bool ACTIVE_FLAT_PICKER = true;
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private ?Request $request;
    private ?Request $mainRequest;
    private string $locale;
    private array $hiddenBlocks = [];
    private array $dynamicBlocks = [];
    private array $associatedElements = [];
    private bool $setGroup = false;
    private bool $disablePicker = false;
    private bool $floatingLabels = true;
    private array $options = [];

    /**
     * FrontType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly IntlRuntime $intlExtension,
        private readonly FileRuntime $fileRuntime,
        private readonly InterfaceHelper $interfaceHelper,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $this->request = $this->coreLocator->requestStack()->getCurrentRequest();
        $this->mainRequest = $this->coreLocator->requestStack()->getMainRequest();
        $this->locale = $this->request->getLocale();
    }

    /**
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entity = $options['form_data'];
        $formName = $builder->getForm()->getName();
        $configuration = $entity->getConfiguration();
        $this->floatingLabels = $configuration->isFloatingLabels();

        $recaptcha = new WidgetType\RecaptchaType($this->coreLocator);
        $recaptcha->add($builder, $configuration);

        if ($entity instanceof FormEntities\Form) {
            $this->setVisibleFields($entity, $formName);
            $this->setForm($entity, $builder);
        } elseif ($entity instanceof FormEntities\StepForm) {
            $this->setStepForm($entity, $builder);
        }
    }

    /**
     * Generate Form.
     *
     * @throws \Exception
     */
    private function setForm(FormEntities\Form $form, FormBuilderInterface $builder): void
    {
        $this->addLayoutFields($form->getLayout(), $builder);
    }

    /**
     * Generate StepForm.
     *
     * @throws \Exception
     */
    private function setStepForm(FormEntities\StepForm $stepForm, FormBuilderInterface $builder): void
    {
        foreach ($stepForm->getForms() as $form) {
            $this->addLayoutFields($form->getLayout(), $builder);
        }
    }

    /**
     * Generate Layout fields.
     *
     * @throws \Exception
     */
    private function addLayoutFields(Layout\Layout $layout, FormBuilderInterface $builder): void
    {
        foreach ($layout->getZones() as $zone) {
            foreach ($zone->getCols() as $col) {
                foreach ($col->getBlocks() as $block) {
                    $fieldType = $block->getBlockType()->getFieldType();
                    if (!empty($fieldType) && !in_array('field_'.$block->getId(), $this->hiddenBlocks)) {
                        $this->setField($fieldType, 'field_'.$block->getId(), $block, $builder);
                    }
                }
            }
        }
    }

    /**
     * Generate field.
     *
     * @throws \Exception
     */
    public function setField(string $fieldType, string $fieldName, Layout\Block $block, FormBuilderInterface $builder, ?FormEntities\ContactValue $value = null, bool $setGroup = false, bool $disablePicker = false): void
    {
        $asText = [Type\DateType::class, Type\DateTimeType::class];
        $fieldType = $value && in_array($fieldType, $asText) ? Type\TextType::class : $fieldType;
        $this->setGroup = $setGroup;
        $this->disablePicker = $disablePicker;
        $this->options = [];
        $this->getOptions($fieldType, $block, $value);
        $builder->add($fieldName, $fieldType, $this->options);
    }

    /**
     * Get options.
     *
     * @throws NonUniqueResultException|MappingException|\Exception
     */
    private function getOptions(string $fieldType, Layout\Block $block, ?FormEntities\ContactValue $value = null): void
    {
        $configuration = $block->getFieldConfiguration();
        $intl = $this->getIntl($block);
        $blockTypeSlug = $block->getBlockType()->getSlug();

        $this->setRequired($fieldType, $configuration, $intl);
        $this->setAutocomplete($fieldType, $blockTypeSlug, $configuration, $intl);
        $this->setLabel($fieldType, $block, $intl);
        $this->setIcon($block);
        $this->setClasses($fieldType, $configuration, $blockTypeSlug);
        $this->setValue($fieldType, $intl, $value);
        $this->setPlaceholder($fieldType, $intl, $configuration);
        $this->setHelp($intl);
        $this->setConstraints($fieldType, $configuration);
        $this->setPicker($fieldType, $configuration);
        $this->setRegEx($blockTypeSlug, $configuration, $intl);
        $this->setChoices($fieldType, $blockTypeSlug, $configuration);
        $this->setEntity($fieldType, $configuration);
        $this->setData($fieldType, $configuration);

        $this->options['attr']['data-floating'] = $this->floatingLabels && !$configuration->isExpanded();

        if (Type\CountryType::class === $fieldType) {
            $this->options['preferred_choices'] = ['FR'];
        }
    }

    /**
     * Set required field.
     */
    private function setRequired(string $fieldType, Layout\FieldConfiguration $configuration, ?Layout\BlockIntl $intl): void
    {
        $excludes = [Type\SubmitType::class];
        if (!in_array($fieldType, $excludes)) {
            $options = [];
            $isRequired = $configuration->isRequired();
            $regex = $configuration->getRegex();
            $intlMessage = $intl instanceof Layout\BlockIntl && $intl->getError() ? $intl->getError() : null;
            $checkBoxMessage = $intlMessage && Type\CheckboxType::class === $fieldType ? $intlMessage : $this->translator->trans('Veuillez accepter', [], 'front_form');
            $message = $checkBoxMessage && Type\CheckboxType::class === $fieldType ? $checkBoxMessage : $intlMessage;
            $this->options['required'] = $isRequired;
            if (!$regex && $isRequired && $message) {
                $options['message'] = $message;
            }
            if ($isRequired) {
                $this->options['constraints'][] = new Assert\NotBlank($options);
            }
        }
    }

    /**
     * Set autocomplete.
     */
    private function setAutocomplete(string $fieldType, string $blockTypeSlug, Layout\FieldConfiguration $configuration, ?Layout\BlockIntl $intl): void
    {
        $configurationSlug = Urlizer::urlize($configuration->getSlug());
        $labelSlug = $intl->getTitle() ? Urlizer::urlize($intl->getTitle()) : $configurationSlug;
        $fullSlug = $configurationSlug.'-'.$labelSlug.'-'.$blockTypeSlug;

        if (Type\EmailType::class === $fieldType) {
            $this->options['attr']['autocomplete'] = 'email';
        } elseif (Type\TelType::class === $fieldType) {
            $this->options['attr']['autocomplete'] = 'tel';
        } elseif (Type\CountryType::class === $fieldType) {
            $this->options['attr']['autocomplete'] = 'country';
        } elseif (Type\LanguageType::class === $fieldType) {
            $this->options['attr']['autocomplete'] = 'language';
        } elseif ('lastname' === $configuration->getSlug()) {
            $this->options['attr']['autocomplete'] = 'family-name';
        } elseif ('firstname' === $configuration->getSlug()) {
            $this->options['attr']['autocomplete'] = 'given-name';
        } elseif (str_contains($fullSlug, 'genre') || str_contains($fullSlug, 'gender') || str_contains($fullSlug, 'civility') || str_contains($fullSlug, 'gender')) {
            $this->options['attr']['autocomplete'] = 'sex';
        } elseif (str_contains($fullSlug, 'company') || str_contains($fullSlug, 'societe') || str_contains($fullSlug, 'entreprise')) {
            $this->options['attr']['autocomplete'] = 'organization-title';
        } elseif (str_contains($fullSlug, 'address') || str_contains($fullSlug, 'adresse')) {
            $this->options['attr']['autocomplete'] = 'address-line1';
        } elseif (str_contains($fullSlug, 'city') || str_contains($fullSlug, 'ville')) {
            $this->options['attr']['autocomplete'] = 'city';
        } elseif ((str_contains($fullSlug, 'firstname') && str_contains($fullSlug, 'lastname'))
            || (str_contains($fullSlug, 'nom') && str_contains($fullSlug, 'prenom'))) {
            $this->options['attr']['autocomplete'] = 'name';
        } elseif ('form-zip-code' === $fullSlug) {
            $this->options['attr']['autocomplete'] = 'postal-code';
        }
    }

    /**
     * Get label.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function setLabel(string $fieldType, Layout\Block $block, ?Layout\BlockIntl $intl): void
    {
        if (Type\HiddenType::class === $fieldType) {
            $this->options['label'] = false;
        } elseif (Type\CheckboxType::class === $fieldType) {
            $values = $block->getFieldConfiguration()->getFieldValues();
            $value = !$values->isEmpty() ? $values->first() : null;
            $intl = $value instanceof Layout\FieldValue ? $this->getIntl($value) : $this->getIntl($block);
            if ($intl instanceof Layout\BlockIntl && $intl->getTitle()) {
                $this->options['label'] = $intl->getTitle();
            } else {
                $this->options['label'] = $intl instanceof Layout\FieldValueIntl && $intl->getIntroduction()
                    ? $intl->getIntroduction() : ($intl instanceof Layout\BlockIntl ? $intl->getIntroduction()
                        : ($value instanceof Layout\FieldValue ? $value->getAdminName() : false));
            }
        } else {
            $this->options['label'] = $intl instanceof Layout\BlockIntl && $intl->getTitle() ? $intl->getTitle() : false;
            if (Type\SubmitType::class === $fieldType && !$this->options['label']) {
                $this->options['label'] = $this->translator->trans('Envoyer', [], 'front_form');
            }
        }
    }

    /**
     * Get icon.
     */
    private function setIcon(Layout\Block $block): void
    {
        if ($block->getIcon()) {
            $this->options['attr']['data-icon'] = $block->getIcon();
        }
    }

    /**
     * Set classes.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private function setClasses(string $fieldType, Layout\FieldConfiguration $configuration, string $blockType): void
    {
        $class = !empty($this->options['attr']['class']) ? $this->options['attr']['class'] : '';
        $groupClass = !empty($this->options['attr']['group']) ? $this->options['attr']['group'] : '';
        $block = $configuration->getBlock();

        if (Type\SubmitType::class === $fieldType) {
            $color = $block->getColor();
            $buttonColor = $color ?: 'btn-danger';
            $buttonID = $configuration->getSlug() ? Urlizer::urlize($configuration->getSlug()) : $block->getId();
            $this->options['attr']['id'] = 'form-submit-track-id-'.$buttonID;
            $this->options['attr']['class'] = $class.' btn '.$buttonColor.' d-flex align-items-center form-submit-track-'.$buttonID;
        } elseif (Type\ChoiceType::class === $fieldType && $configuration->isMultiple() && $configuration->isPicker() && !$configuration->isExpanded()) {
            $this->options['attr']['class'] = $class.' select-choice';
        } elseif (Type\CheckboxType::class === $fieldType || (Type\ChoiceType::class === $fieldType && $configuration->isExpanded())) {
            $this->options['display'] = 'form-check';
            if (Type\ChoiceType::class === $fieldType && $configuration->isInline()) {
                $this->options['label_class'] = 'form-check-label';
            } elseif (Type\ChoiceType::class === $fieldType) {
                $this->options['label_class'] = 'form-choice-label';
            }
            if ($configuration->isExpanded()) {
                $this->options['attr']['data-floating'] = false;
            }
        } elseif (Type\FileType::class === $fieldType && $block->getColor() && str_contains($block->getColor(), 'btn')) {
            $this->options['row_attr']['class'] = 'file-group as-btn as-'.$block->getColor();
        }

        if ($configuration->isInline()) {
            $this->options['attr']['class'] = $class.' form-check-inline me-0';
        }

        if (in_array($block->getId(), $this->dynamicBlocks)) {
            $this->options['attr']['class'] = $class.' dynamic-field';
        }

        if (!empty($this->associatedElements[$block->getId()])) {
            $this->options['attr']['data-elements'] = json_encode($this->associatedElements[$block->getId()]);
        }

        $matches = explode('\\', $fieldType);
        $type = str_replace('Type', '', end($matches));

        if ($this->setGroup) {
            $block = $configuration->getBlock();
            $blockSize = $block->getSize();
            $colSize = $block->getCol()->getSize();
            $size = $colSize < $blockSize ? $colSize : $blockSize;
            $this->options['attr']['group'] = $groupClass = strtolower($type).'-group col-md-'.$size.' '.$blockType.'-group';
        } else {
            $this->options['attr']['group'] = $groupClass = strtolower($type).'-group '.$blockType.'-group';
        }

        if ($configuration->isSmallSize()) {
            $this->options['attr']['group'] = trim($groupClass.' small-size');
        }

        if ($block->getIcon()) {
            $this->options['attr']['addon'] = $block->getIcon();
        }

        if (Type\FileType::class === $fieldType && $block->getColor() && str_contains($block->getColor(), 'btn')) {
            $this->options['attr']['data-color'] = str_replace('btn-', '', $block->getColor());
            $this->options['attr']['class'] = $class.' '.$this->options['attr']['data-color'];
            if ($block->isControls()) {
                $blockModel = BlockModel::fromEntity($block, $this->coreLocator);
                $this->options['attr']['group'] = $groupClass.' '.trim('as-btn '.$this->options['attr']['data-color']);
                $this->options['attr']['data-type'] = 'as-btn';
                $this->options['attr']['data-label'] = $blockModel->intl->linkLabel ?: $this->translator->trans('Parcourir', [], 'front_form');
            }
        }
    }

    /**
     * Get value.
     *
     * @throws \Exception
     */
    private function setValue(string $fieldType, ?Layout\BlockIntl $intl, ?FormEntities\ContactValue $value): void
    {
        $data = !empty($this->options['data']) ? $this->options['data'] : null;
        if (Type\HiddenType::class === $fieldType) {
            $data = $intl && $intl->getTitle() ? $intl->getTitle() : null;
        } elseif (Type\DateType::class === $fieldType && $value instanceof FormEntities\ContactValue) {
            $data = $value->getValue() ? new \DateTime($value->getValue()) : null;
        } elseif (Type\CheckboxType::class === $fieldType && $value instanceof FormEntities\ContactValue) {
            $data = boolval($value->getValue());
        }

        if ($data) {
            $this->options['data'] = $data;
        }
    }

    /**
     * Get placeholder.
     *
     * @throws \Exception
     */
    private function setPlaceholder(string $fieldType, ?Layout\BlockIntl $intl, Layout\FieldConfiguration $configuration): void
    {
        $exceptionFields = [];
        $excludesFields = [Type\SubmitType::class];
        $optionsFields = [Type\ChoiceType::class, Type\CountryType::class, EntityType::class];
        $datesFields = [Type\DateType::class, Type\DateTimeType::class, Type\TimeType::class];
        $exceptionFields = array_merge($exceptionFields, $datesFields);
        $isOptions = in_array($fieldType, $optionsFields);
        $placeholder = $intl instanceof Layout\BlockIntl && $intl->getPlaceholder() ? $intl->getPlaceholder() : ($isOptions ? $this->translator->trans('Sélectionnez', [], 'front_form') : null);

        if (Type\FileType::class === $fieldType && !$placeholder) {
            $placeholder = $this->translator->trans('Aucun fichier sélectionné', [], 'front_form');
        }

        if (Type\FileType::class === $fieldType) {
            $this->options['attr']['placeholder-btn'] = $intl && $intl->getTargetLabel()
                ? $intl->getTargetLabel() : $this->translator->trans('Parcourir', [], 'front_form');
        }

        if ($this->floatingLabels && !in_array($fieldType, $exceptionFields) && !$placeholder) {
            $placeholder = !empty($this->options['label']) ? $this->options['label'] : null;
        }

        if (in_array($fieldType, $excludesFields) || !$placeholder && !in_array($fieldType, $exceptionFields)) {
            return;
        }

        if ($isOptions) {
            $this->options['placeholder'] = $placeholder;
            $this->options['attr']['data-placeholder'] = $placeholder;
        } elseif (!$configuration->isPicker() && in_array($fieldType, $datesFields)) {
            $this->getDatesOptions($configuration, $fieldType);
        } else {
            $this->options['attr']['placeholder'] = $placeholder;
            $this->options['attr']['data-placeholder'] = $placeholder;
        }
    }

    /**
     * Get field Date options.
     *
     * @throws \Exception
     */
    private function getDatesOptions(Layout\FieldConfiguration $configuration, string $fieldType): void
    {
        /* Placeholder */
        $this->options['placeholder'] = Type\TimeType::class !== $fieldType ? [
            'year' => $this->translator->trans('Année', [], 'front_form'),
            'month' => $this->translator->trans('Mois', [], 'front_form'),
            'day' => $this->translator->trans('Jour', [], 'front_form'),
            'hour' => $this->translator->trans('Heure', [], 'front_form'),
            'minute' => $this->translator->trans('Minute', [], 'front_form'),
            'second' => $this->translator->trans('Seconde', [], 'front_form'),
        ] : [
            'hour' => $this->translator->trans('Heure', [], 'front_form'),
            'minute' => $this->translator->trans('Minute', [], 'front_form'),
        ];

        if (Type\TimeType::class !== $fieldType) {
            /* Year options */
            $minData = $configuration->getMin();
            $maxData = $configuration->getMax();
            if ($maxData && !$minData || $minData > $maxData && !empty($maxData)) {
                return;
            }

            $startDatetime = $minData > 0 ? new \DateTime($minData.'-01-01') : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $referDatetimeStart = $minData > 0 ? new \DateTime($minData.'-01-01') : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $start = intval($startDatetime->format('Y'));
            $endDatetime = $maxData > 0 ? new \DateTime($maxData.'-01-01', new \DateTimeZone('Europe/Paris')) : $referDatetimeStart->add(new \DateInterval('P100Y'));
            $end = intval($endDatetime->format('Y'));

            $years = [];
            for ($y = $start; $y <= $end; ++$y) {
                $years[] = $y;
            }

            $this->options['years'] = $years;
        }
    }

    /**
     * Get helper.
     */
    private function setHelp(?Layout\BlockIntl $intl): void
    {
        $help = $intl instanceof Layout\BlockIntl ? $intl->getHelp() : null;
        if ($help) {
            $this->options['help'] = $help;
        }
    }

    /**
     * Get Constraints.
     */
    private function setConstraints(string $fieldType, Layout\FieldConfiguration $configuration): void
    {
        $excludes = [Type\SubmitType::class];
        if (!in_array($fieldType, $excludes)) {
            if (Type\EmailType::class === $fieldType) {
                $this->options['constraints'][] = new Assert\Email();
            } elseif (Type\UrlType::class === $fieldType) {
                $this->options['constraints'][] = new Assert\Url();
            }
        }

        $excludes = [Type\DateType::class, Type\DateTimeType::class, Type\TimeType::class, Type\FileType::class];
        if (!in_array($fieldType, $excludes)) {
            if ($configuration->getMin() || $configuration->getMax()) {
                $block = $configuration->getBlock();
                $blockType = $block?->getBlockType();
                $isInteger = $block && $blockType && 'Symfony\Component\Form\Extension\Core\Type\IntegerType' == $blockType->getFieldType();
                if (is_numeric($configuration->getMin())) {
                    $isInteger ? $this->options['attr']['min'] = intval($configuration->getMin()) : $this->options['attr']['minlength'] = intval($configuration->getMin());
                }
                if (is_numeric($configuration->getMax())) {
                    $isInteger ? $this->options['attr']['max'] = intval($configuration->getMax()) : $this->options['attr']['maxlength'] = intval($configuration->getMax());
                }
                if ($isInteger) {
                    if ($configuration->getMin()) {
                        $this->options['constraints'][] = new Assert\GreaterThanOrEqual([
                            'value' => $configuration->getMin(),
                        ]);
                    }
                    if ($configuration->getMax()) {
                        $this->options['constraints'][] = new Assert\LessThanOrEqual([
                            'value' => $configuration->getMax(),
                        ]);
                    }
                } else {
                    $this->options['constraints'][] = new Assert\Length([
                        'min' => is_numeric($configuration->getMin()) ? intval($configuration->getMin()) : false,
                        'max' => is_numeric($configuration->getMax()) ? intval($configuration->getMax()) : false,
                    ]);
                }
            }
        }

        if (Type\FileType::class === $fieldType) {
            $fileTypes = $configuration->getFilesTypes();

            $this->options['multiple'] = $configuration->isMultiple();
            $this->options['help'] = $this->getFileHelp($fileTypes);
            if ($this->options['multiple']) {
                $this->options['data_class'] = null;
            }
            if ($configuration->getBlock()->getColor()) {
                $this->options['attr']['data-btn'] = $configuration->getBlock()->getColor();
            }

            /** Constraints */
            $constraints = [];
            $maxFilesize = $configuration->getMaxFileSize() ?: $this->fileRuntime->convertToKilobytes(ini_get('upload_max_filesize'));
            if ($maxFilesize) {
                $constraints['maxSize'] = $maxFilesize.'k';
                $this->options['attr']['data-max-size'] = $maxFilesize;
                $this->options['attr']['data-max-size-message'] = $this->translator->trans('Le fichier %name% est trop volumineux. La taille maximale autorisée est %limit%%suffix%.', [
                    '%limit%' => $maxFilesize,
                    '%suffix%' => 'k',
                ], 'validators');
            }
            $mimeTypes = $this->getMimeTypes($fileTypes);
            if ($fileTypes && !empty($mimeTypes['mimeTypes']) && !empty($mimeTypes['accept'])) {
                $this->options['attr']['accept'] = $mimeTypes['accept'];
                if (!$this->options['multiple']) {
                    $constraints['mimeTypes'] = $mimeTypes['mimeTypes'];
                }
            }
            if ($constraints) {
                if ($configuration->isMultiple()) {
                    $constraints = new Assert\File($constraints);
                    $this->options['constraints'][] = new Assert\All($constraints);
                } else {
                    $this->options['constraints'][] = new Assert\File($constraints);
                }
            }
        } elseif (Type\TelType::class === $fieldType) {
            $this->options['constraints'][] = new Validator\Phone();
        }
    }

    /**
     * Set field as picker.
     *
     * @throws \Exception
     */
    private function setPicker(string $fieldType, Layout\FieldConfiguration $configuration): void
    {
        if ($configuration->isPicker()) {
            $class = !empty($this->options['attr']['class']) ? $this->options['attr']['class'] : '';
            $fieldsDate = [Type\DateType::class, Type\DateTimeType::class, Type\TimeType::class];
            if (in_array($fieldType, $fieldsDate) && !$this->disablePicker) {
                $flatPickerClass = Type\DateTimeType::class === $fieldType || Type\TimeType::class === $fieldType ? $class.' flatpicker' : $class.' mc-datepicker';
                $class = self::ACTIVE_FLAT_PICKER ? $flatPickerClass : $class.' datepicker';
                $type = $configuration->getButtonType();
                $this->options['widget'] = 'single_text';
                $this->options['html5'] = false;
                $this->options['attr']['class'] = $class;
                $this->options['attr']['data-type'] = Type\DateType::class === $fieldType ? 'date' : (Type\DateTimeType::class === $fieldType ? 'datetime' : 'hour');
                if ($type) {
                    if ('before-current-in' === $type) {
                        $this->options['attr']['data-max'] = (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
                    } elseif ('after-current-in' === $type) {
                        $this->options['attr']['data-min'] = (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
                    } elseif ('before-current-out' === $type) {
                        $this->options['attr']['data-max'] = (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->modify('-1 day')->format('Y-m-d');
                    } elseif ('after-current-out' === $type) {
                        $this->options['attr']['data-min'] = (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->modify('+1 day')->format('Y-m-d');
                    }
                } else {
                    if ($configuration->getMin()) {
                        $this->options['attr']['data-min'] = (new \DateTime($configuration->getMin().'-01-01', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
                    }
                    if ($configuration->getMax()) {
                        $this->options['attr']['data-max'] = (new \DateTime($configuration->getMax().'-12-31', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
                    }
                }
                if (Type\DateType::class === $fieldType) {
                    $this->options['format'] = $this->intlExtension->formatDate($this->locale)->datepickerPHP;
                }
            } elseif (Type\CountryType::class === $fieldType || Type\LanguageType::class || EntityType::class) {
                $this->options['attr']['class'] = $class.' select-choice';
            }
        }
    }

    /**
     * Set regEx.
     */
    private function setRegEx(string $blockType, Layout\FieldConfiguration $configuration, ?Layout\BlockIntl $intl): void
    {
        $regex = $configuration->getRegex();
        $firstValid = $regex ? str_starts_with($regex, '/') : null;
        $lastValid = $regex ? str_ends_with($regex, '/') : null;
        if ($regex && $firstValid && $lastValid) {
            $message = $intl instanceof Layout\BlockIntl && $intl->getError() ? $intl->getError() : $this->translator->trans('This value is not valid.', [], 'validators');
            $this->options['constraints'][] = new Assert\Regex([
                'message' => $message,
                'pattern' => $regex,
            ]);
        } elseif ('form-zip-code' === $blockType) {
            $this->options['constraints'][] = new Validator\ZipCode();
        }
    }

    /**
     * Set Choices.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private function setChoices(string $fieldType, string $blockType, Layout\FieldConfiguration $configuration): void
    {
        if (Type\ChoiceType::class === $fieldType) {
            $class = !empty($this->options['attr']['class']) ? $this->options['attr']['class'] : '';
            $this->options['multiple'] = $configuration->isMultiple();
            $this->options['expanded'] = $configuration->isExpanded() && !$configuration->isPicker();
            $this->options['choices'] = [];
            if (!$this->options['expanded'] && $configuration->isPicker()) {
                $this->options['attr']['class'] = $class.' select-choice';
            }
            foreach ($configuration->getFieldValues() as $key => $value) {
                if (!$value->getValue()) {
                    $valueModel = ViewModel::fromEntity($value, $this->coreLocator);
                    if ($value->getValues()->isEmpty()) {
                        $asEmail = 'form-emails' === $blockType && filter_var($valueModel->intl->body, FILTER_VALIDATE_EMAIL);
                        $value = $asEmail ? $key.'-email-'.$valueModel->intl->body : $valueModel->intl->body;
                        $this->options['choices'][$valueModel->intl->introduction] = $value;
                    } else {
                        foreach ($value->getValues() as $subKey => $subValue) {
                            $subValueModel = ViewModel::fromEntity($subValue, $this->coreLocator);
                            $asEmail = 'form-emails' === $blockType && filter_var($subValueModel->intl->body, FILTER_VALIDATE_EMAIL);
                            $subValue = $asEmail ? $subKey.'-email-'.$subValueModel->intl->body : $valueModel->intl->body;
                            $this->options['choices'][$valueModel->intl->introduction][$subValueModel->intl->introduction] = $subValue;
                        }
                    }
                }
            }
        }
    }

    /**
     * Set Entity Type options.
     */
    private function setEntity(string $fieldType, Layout\FieldConfiguration $configuration): void
    {
        if (EntityType::class === $fieldType) {
            $fieldName = $configuration->getSlug();
            $requestArg = $fieldName ? $this->mainRequest->get($fieldName) : null;
            $this->options['class'] = $configuration->getClassName();
            if ($requestArg) {
                $this->options['data'] = $this->entityManager->getRepository($this->options['class'])->findOneBy(['slug' => $requestArg]);
            }
            $this->options['multiple'] = $configuration->isMultiple();
            $this->options['expanded'] = $configuration->isExpanded();
            $this->options['query_builder'] = function (EntityRepository $er) use ($configuration) {
                $className = $er->getClassName();
                $entities = $this->entityManager->getRepository($className)->findAll();
                $referEntity = $entities ? $entities[0] : null;
                if ($referEntity) {
                    $website = $this->entityManager->getRepository(Website::class)->findOneByHost($this->request->getHost());
                    $interface = $this->interfaceHelper->generate($className);
                    $masterGetter = !empty($interface['masterField']) ? 'get'.ucfirst($interface['masterField']) : null;
                    $masterWebsite = $masterGetter && method_exists($referEntity, $masterGetter) && method_exists($referEntity->$masterGetter(), 'getWebsite');
                    if ($masterGetter && $masterWebsite) {
                        $qb = $er->createQueryBuilder('e')
                            ->leftJoin('e.'.$interface['masterField'], 'j')
                            ->andWhere('j.website = :website')
                            ->setParameter('website', $website->entity)
                            ->addSelect('j')
                            ->orderBy('e.position', 'ASC');
                    } elseif (method_exists($referEntity, 'getWebsite')) {
                        $qb = $er->createQueryBuilder('e')
                            ->andWhere('e.website = :website')
                            ->setParameter('website', $website->entity)
                            ->orderBy('e.position', 'ASC');
                    } else {
                        $qb = $er->createQueryBuilder('e')
                            ->orderBy('e.position', 'ASC');
                    }
                    if (method_exists($referEntity, 'getUrls')) {
                        $qb->leftJoin('e.urls', 'u')
                            ->andWhere('u.locale = :locale')
                            ->andWhere('u.online = :online')
                            ->setParameter('locale', $this->locale)
                            ->setParameter('online', true)
                            ->addSelect('u');
                    }
                    if ($configuration->getMasterField()) {
                        $matches = explode('-', $configuration->getMasterField());
                        $qb->leftJoin('e.'.$matches[0], $matches[0])
                            ->andWhere($matches[0].'.slug = :'.$matches[0])
                            ->setParameter($matches[0], end($matches))
                            ->addSelect($matches[0]);
                    }

                    return $qb;
                }

                return null;
            };
            $this->options['choice_label'] = function ($entity) {
                return strip_tags($entity->getAdminName());
            };
        }
    }

    /**
     * Set field data.
     */
    private function setData(string $fieldType, Layout\FieldConfiguration $configuration): void
    {
//        if (Type\HiddenType::class === $fieldType && 'formation-name' === $configuration->getSlug() && $this->coreLocator->request()->get('code')) {
//            $formation = $this->coreLocator->em()->getRepository(Product::class)->find($this->coreLocator->request()->get('code'));
//            $formation = $formation ? ProductModel::fromEntity($formation, $this->coreLocator, [
//                'disabledLayout' => true,
//                'disabledMedias' => true,
//                'disabledCategories' => true,
//                'disabledCategory' => true,
//            ]) : null;
//            if ($formation) {
//                $this->options['data'] = $formation->intl->title;
//            }
//        }
    }

    /**
     * Get intl.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function getIntl(mixed $entity): ?object
    {
        return IntlModel::fromEntity($entity, $this->coreLocator)->intl;
    }

    /**
     * Get File help message.
     */
    private function getFileHelp(array $fileTypes = []): ?string
    {
        $help = '';
        $haveImages = false;
        foreach ($fileTypes as $fileType) {
            if ('image/*' === $fileType) {
                $fileType = 'jpg, jpeg, png, heic';
                $haveImages = true;
            }
            $help .= $fileType.', ';
        }

        $help = rtrim($help, ', ');
        $message = $haveImages || count($fileTypes) > 1
            ? $this->translator->trans('Formats acceptés :', [], 'front_form')
            : $this->translator->trans('Format accepté :', [], 'front_form');

        return $help ? $message.' '.$help : (!empty($this->options['help']) ? $this->options['help'] : null);
    }

    /**
     * Get mime types for constraints.
     */
    private function getMimeTypes(array $fileTypes): array
    {
        $allMimeTypes = [
            '.xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            '.xls' => 'application/vnd.ms-excel',
            'image/*' => 'image/*',
            '.doc' => 'application/msword',
            '.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            '.txt' => 'text/plain',
            '.pdf' => 'application/pdf',
            '.mp4' => 'video/mp4',
            '.mp3' => 'audio/mpeg',
        ];

        $accept = '';
        $mimeTypes = [];
        foreach ($fileTypes as $fileType) {
            $accept .= $fileType.', ';
            if (!empty($allMimeTypes[$fileType])) {
                $mimeTypes[] = $allMimeTypes[$fileType];
            }
        }

        return [
            'accept' => rtrim($accept, ', '),
            'mimeTypes' => $mimeTypes,
        ];
    }

    /**
     * Generate Form.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function setVisibleFields(FormEntities\Form $form, string $formName): void
    {
        if ($form->getConfiguration() && $form->getConfiguration()->isDynamic()) {
            $layout = $form->getLayout();
            if ($layout instanceof Layout\Layout) {
                $elements = $this->getAssociatedElements($layout);
                $associatedElements = $elements['associatedElements'];
                $fieldsNames = $elements['fieldsNames'];
                $allElements = $elements['allElements'];
                $this->setBlocksVisibly($fieldsNames, $formName, $associatedElements, $allElements);
            }
        }
    }

    /**
     * To get associated elements.
     */
    private function getAssociatedElements(Layout\Layout $layout): array
    {
        $associatedElements = [];
        $fieldsNames = [];
        $allElements = [];

        foreach ($layout->getZones() as $zone) {
            $allElements['zones'][$zone->getId()] = $zone;
            foreach ($zone->getCols() as $col) {
                $allElements['cols'][$col->getId()] = $col;
                foreach ($col->getBlocks() as $block) {
                    $allElements['blocks'][$block->getId()] = $block;
                    $fieldsNames[] = 'field_'.$block->getId();
                    $fieldConfiguration = $block->getFieldConfiguration();
                    if ($fieldConfiguration instanceof Layout\FieldConfiguration) {
                        foreach ($fieldConfiguration->getAssociatedElements() as $element) {
                            $this->dynamicBlocks[] = $block->getId();
                            $elementsMatches = explode('-', $element);
                            $associatedElements['field_'.$block->getId()][$elementsMatches[0].'s'][] = $elementsMatches[1];
                        }
                        foreach ($fieldConfiguration->getFieldValues() as $value) {
                            if ($value->getAssociatedElements()) {
                                $this->dynamicBlocks[] = $block->getId();
                                foreach ($value->getAssociatedElements() as $element) {
                                    $elementsMatches = explode('-', $element);
                                    $associatedElements['field_'.$block->getId()][$elementsMatches[0].'s'][] = $elementsMatches[1];
                                }
                            }
                        }
                    }
                }
            }
        }

        return [
            'associatedElements' => $associatedElements,
            'fieldsNames' => $fieldsNames,
            'allElements' => $allElements,
        ];
    }

    /**
     * To set blocks visibility.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function setBlocksVisibly(array $fieldsNames, string $formName, array $associatedElements, array $allElements): void
    {
        foreach ($fieldsNames as $fieldName) {
            $value = !empty($this->request->request->all()[$formName][$fieldName]) ? $this->request->request->all()[$formName][$fieldName] : null;
            $value = $value ? str_replace('&nbsp;', ' ', $value) : null;
            if (is_string($value) && str_contains($value, '-email-')) {
                $matches = explode('-email-', $value);
                $value = end($matches);
            }
            if (!empty($associatedElements[$fieldName])) {
                $asHidden = empty($value);
                foreach ($associatedElements[$fieldName] as $type => $elements) {
                    foreach ($elements as $elementId) {
                        $element = !empty($allElements[$type][$elementId]) ? $allElements[$type][$elementId] : null;
                        $matches = explode('_', $fieldName);
                        $parentBlockId = end($matches);
                        $parentBlock = !empty($allElements['blocks'][$parentBlockId]) ? $allElements['blocks'][$parentBlockId] : null;
                        if ($parentBlock instanceof Layout\Block) {
                            $parentBlockConfiguration = $parentBlock->getFieldConfiguration();
                            if ($parentBlockConfiguration instanceof Layout\FieldConfiguration) {
                                $fieldValues = $parentBlockConfiguration->getFieldValues();
                                $blockTypeSlug = $parentBlock->getBlockType()->getSlug();
                                $elementsCheckCountValues = ['form-choice-type', 'form-emails'];
                                $checkValues = in_array($blockTypeSlug, $elementsCheckCountValues);
                                $asHidden = $fieldValues->count() > 0 && $checkValues || !$checkValues && empty($value);
                                if ($checkValues && $asHidden) {
                                    $prefix = $element instanceof Layout\Zone ? 'zone' : ($element instanceof Layout\Col ? 'col' : 'block');
                                    foreach ($fieldValues as $fieldValue) {
                                        $intl = $this->getIntl($fieldValue);
                                        $valueToCheck = $intl?->getBody();
                                        if (is_array($value)) {
                                            foreach ($value as $item) {
                                                if ($valueToCheck === $item && in_array($prefix.'-'.$elementId, $fieldValue->getAssociatedElements())) {
                                                    $asHidden = false;
                                                    break;
                                                }
                                            }
                                        } elseif ($valueToCheck === $value && in_array($prefix.'-'.$elementId, $fieldValue->getAssociatedElements())) {
                                            $asHidden = false;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        if ($element instanceof Layout\Block) {
                            if ($asHidden) {
                                $this->hiddenBlocks[] = 'field_'.$element->getId();
                            }
                            $this->associatedElements[$parentBlock->getId()][] = $element->getId();
                        } elseif ($element instanceof Layout\Col) {
                            foreach ($element->getBlocks() as $block) {
                                if ($asHidden) {
                                    $this->hiddenBlocks[] = 'field_'.$block->getId();
                                }
                                $this->associatedElements[$parentBlock->getId()][] = $element->getId();
                            }
                        } elseif ($element instanceof Layout\Zone) {
                            foreach ($element->getCols() as $col) {
                                foreach ($col->getBlocks() as $block) {
                                    if ($asHidden) {
                                        $this->hiddenBlocks[] = 'field_'.$block->getId();
                                    }
                                    $this->associatedElements[$parentBlock->getId()][] = $element->getId();
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->hiddenBlocks = array_unique($this->hiddenBlocks);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'form_data' => null,
            'translation_domain' => 'front_form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'front_form';
    }
}
