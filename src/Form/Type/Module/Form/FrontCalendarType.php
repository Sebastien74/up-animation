<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * FrontCalendarType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontCalendarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dates = $options['dates'];

        $choices = [];

        if (is_object($dates) && property_exists($dates, 'dates')) {
            foreach ($dates->dates as $date => $config) {
                if (!empty($config['occurrences'])) {
                    foreach ($config['occurrences'] as $occurrence) {
                        if ('available' === $occurrence['available']) {
                            $value = $occurrence['datetime']->format('Y-m-d H:i:s');
                            $choices[$value] = $value;
                        }
                    }
                }
            }
        }

        $builder->add('slot_date', ChoiceType::class, [
            'label' => false,
            'multiple' => false,
            'expanded' => true,
            'customized_options' => $dates,
            'choices' => $choices,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'dates' => [],
        ]);
    }
}
