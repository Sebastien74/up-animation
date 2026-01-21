<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Module\Form\CalendarAppointment;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CalendarAppointmentType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarAppointmentType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CalendarAppointmentType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var CalendarAppointment $appointment */
        $appointment = $builder->getData();

        $builder->add('appointmentDate', Type\DateTimeType::class, [
            'label' => $this->translator->trans('Heure du rendez-vous', [], 'admin'),
            'attr' => ['group' => 'col-md-3'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('contactForm', CalendarAppointmentContactType::class, ['form_data' => $appointment->getContactForm()]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarAppointment::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
