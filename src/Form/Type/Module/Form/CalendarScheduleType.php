<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Module\Form\CalendarSchedule;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CalendarScheduleType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarScheduleType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CalendarScheduleType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('timeRanges', Type\CollectionType::class, [
            'label' => false,
            'entry_type' => CalendarTimeRangeType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => ['attr' => ['icon' => 'fal clock', 'group' => 'col-md-3']],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarSchedule::class,
            'website' => null,
            'legend' => $this->translator->trans('Jours de la semaine', [], 'admin'),
            'translation_domain' => 'admin',
        ]);
    }
}
