<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Module\Form\ContactForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CalendarAppointmentContactType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarAppointmentContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('contactValues', CollectionType::class, [
            'entry_type' => CalendarAppointmentContactValuesType::class,
            'entry_options' => ['collection' => $options['form_data']->getContactValues()],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactForm::class,
            'website' => null,
            'form_data' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
