<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Module\Form\CalendarTimeRange;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CalendarTimeRangeType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarTimeRangeType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CalendarTimeRangeType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('startHour', Type\TimeType::class, [
            'label' => $this->translator->trans('Ouverture', [], 'admin'),
            'attr' => ['group' => 'hours-field-group col-md-6'],
            'placeholder' => [
                'hour' => $this->translator->trans('Heure', [], 'admin'),
                'minute' => $this->translator->trans('Minute', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('endHour', Type\TimeType::class, [
            'label' => $this->translator->trans('Fermeture', [], 'admin'),
            'attr' => ['group' => 'hours-field-group col-md-6'],
            'placeholder' => [
                'hour' => $this->translator->trans('Heure', [], 'admin'),
                'minute' => $this->translator->trans('Minute', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarTimeRange::class,
            'website' => null,
            'legend' => $this->translator->trans('Plages horaires', [], 'admin'),
            'legend_property' => 'adminName',
            'translation_domain' => 'admin',
        ]);
    }
}
