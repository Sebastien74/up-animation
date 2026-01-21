<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Form\Validator\UniqDate;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PublicationDatesType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PublicationDatesType
{
    private TranslatorInterface $translator;

    /**
     * PublicationDatesType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * Add fields.
     * @throws Exception
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $requiredFields = !empty($options['required_fields']) ? $options['required_fields'] : [];
        $uniqFields = !empty($options['uniq_fields']) ? $options['uniq_fields'] : [];
        $entity = !empty($options['entity']) ? $options['entity'] : $builder->getData();
        $asDatePicker = !empty($options['datePicker']) && $options['datePicker'];
        $years = $this->getYears();

        $constraints = in_array('publicationStart', $requiredFields) ? [new Assert\NotBlank()] : [];
        if (in_array('publicationStart', $uniqFields)) {
            $constraints[] = new UniqDate();
        }

        $arguments = [
            'required' => in_array('publicationStart', $requiredFields),
            'label' => !empty($options['startLabel']) ? $options['startLabel'] : $this->translator->trans('Début de la publication', [], 'admin'),
            'placeholder' => $asDatePicker ? $this->translator->trans('Sélectionnez une date', [], 'admin') : $this->placeholders(),
            'widget' => $asDatePicker ? 'single_text' : null,
            'format' => $asDatePicker ? 'dd/MM/YYYY HH:mm' : DateTimeType::HTML5_FORMAT,
            'years' => $years,
            'attr' => [
                'group' => !empty($options['startGroup']) ? $options['startGroup'].'  datetime-group' : 'col-md-4 datetime-group',
                'class' => $asDatePicker ? 'datepicker' : null,
                'placeholder' => $asDatePicker ? $this->translator->trans('Sélectionnez une date', [], 'admin') : null,
                'data-config' => $options['data-config'] ?? true,
            ],
            'constraints' => $constraints,
        ];

        if (empty($options['disabled_set_data'])) {
            $publicationStart = is_object($entity) && $entity->getPublicationStart() ? $entity->getPublicationStart() : null;
            if (empty($options['disabled_default'])) {
                $publicationStart = is_object($entity) && $entity->getPublicationStart() ? $entity->getPublicationStart() : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            }
            $arguments['data'] = $publicationStart;
        }

        $builder->add('publicationStart', DateTimeType::class, $arguments);

        $constraints = in_array('publicationEnd', $requiredFields) ? [new Assert\NotBlank()] : [];
        if (in_array('publicationEnd', $uniqFields)) {
            $constraints[] = new UniqDate();
        }
        $builder->add('publicationEnd', DateTimeType::class, [
            'required' => in_array('publicationEnd', $requiredFields),
            'label' => !empty($options['endLabel']) ? $options['endLabel'] : $this->translator->trans('Fin de la publication', [], 'admin'),
            'placeholder' => $asDatePicker ? $this->translator->trans('Sélectionnez une date', [], 'admin') : $this->placeholders(),
            'widget' => $asDatePicker ? 'single_text' : null,
            'format' => $asDatePicker ? 'dd/MM/YYYY HH:mm' : DateTimeType::HTML5_FORMAT,
            'years' => $years,
            'attr' => [
                'group' => !empty($options['endGroup']) ? $options['endGroup'].'  datetime-group' : 'col-md-4 datetime-group',
                'class' => $asDatePicker ? 'datepicker' : null,
                'placeholder' => $asDatePicker ? $this->translator->trans('Sélectionnez une date', [], 'admin') : null,
                'data-config' => $options['data-config'] ?? true,
            ],
            'constraints' => $constraints,
        ]);
    }

    /**
     * Get years.
     *
     * @throws Exception
     */
    private function getYears(): array
    {
        $today = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $currentYear = intval($today->format('Y'));
        $years = [$currentYear];
        for ($i = $currentYear; $i <= ($currentYear + 10); ++$i) {
            $years[] = $i;
        }
        for ($i = $currentYear; $i > ($currentYear - 11); --$i) {
            $years[] = $i;
        }
        sort($years);

        return array_unique($years);
    }

    /**
     * Get placeholders.
     */
    private function placeholders(): array
    {
        return [
            'year' => $this->translator->trans('Année', [], 'admin'),
            'month' => $this->translator->trans('Mois', [], 'admin'),
            'day' => $this->translator->trans('Jour', [], 'admin'),
            'hour' => $this->translator->trans('Heure', [], 'admin'),
            'minute' => $this->translator->trans('Minute', [], 'admin'),
            'second' => $this->translator->trans('Seconde', [], 'admin'),
        ];
    }
}
