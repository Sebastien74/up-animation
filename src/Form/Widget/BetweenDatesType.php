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
 * BetweenDatesType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BetweenDatesType
{
    private TranslatorInterface $translator;

    /**
     * BetweenDatesType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * Add fields.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $requiredFields = !empty($options['required_fields']) ? $options['required_fields'] : [];
        $uniqFields = !empty($options['uniq_fields']) ? $options['uniq_fields'] : [];
        $asDatePicker = !empty($options['datePicker']) && $options['datePicker'];
        $years = $this->getYears();

        $constraints = in_array('startDate', $requiredFields) ? [new Assert\NotBlank()] : [];
        if (in_array('startDate', $uniqFields)) {
            $constraints[] = new UniqDate();
        }

        $arguments = [
            'required' => in_array('startDate', $requiredFields),
            'label' => !empty($options['startLabel']) ? $options['startLabel'] : $this->translator->trans('Début', [], 'admin'),
            'placeholder' => $asDatePicker ? $this->translator->trans('Sélectionnez une date', [], 'admin') : $this->placeholders(),
            'widget' => $asDatePicker ? 'single_text' : null,
            'format' => $asDatePicker ? 'dd/MM/YYYY HH:mm' : DateTimeType::HTML5_FORMAT,
            'years' => $years,
            'attr' => [
                'group' => !empty($options['startGroup']) ? $options['startGroup'].'  datetime-group' : 'col-md-4 datetime-group',
                'class' => $asDatePicker ? 'datepicker' : null,
                'placeholder' => $asDatePicker ? $this->translator->trans('Sélectionnez une date', [], 'admin') : null,
            ],
            'constraints' => $constraints,
        ];

        $builder->add('startDate', DateTimeType::class, $arguments);

        $constraints = in_array('endDate', $requiredFields) ? [new Assert\NotBlank()] : [];
        if (in_array('endDate', $uniqFields)) {
            $constraints[] = new UniqDate();
        }
        $builder->add('endDate', DateTimeType::class, [
            'required' => in_array('endDate', $requiredFields),
            'label' => !empty($options['endLabel']) ? $options['endLabel'] : $this->translator->trans('Fin', [], 'admin'),
            'placeholder' => $asDatePicker ? $this->translator->trans('Sélectionnez une date', [], 'admin') : $this->placeholders(),
            'widget' => $asDatePicker ? 'single_text' : null,
            'format' => $asDatePicker ? 'dd/MM/YYYY HH:mm' : DateTimeType::HTML5_FORMAT,
            'years' => $years,
            'attr' => [
                'group' => !empty($options['endGroup']) ? $options['endGroup'].'  datetime-group' : 'col-md-4 datetime-group',
                'class' => $asDatePicker ? 'datepicker' : null,
                'placeholder' => $asDatePicker ? $this->translator->trans('Sélectionnez une date', [], 'admin') : null,
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
