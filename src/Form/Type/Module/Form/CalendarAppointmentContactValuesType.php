<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Module\Form\ContactValue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CalendarAppointmentContactValuesType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarAppointmentContactValuesType extends AbstractType
{
    /**
     * CalendarAppointmentContactValuesType constructor.
     */
    public function __construct(private readonly FrontType $frontType)
    {
    }

    /**
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $disabled = ['["true"]', FileType::class];
        $values = $options['collection']->getValues();
        $value = !empty($values[$builder->getName()]) ? $values[$builder->getName()] : null;

        if ($value instanceof ContactValue) {
            $fieldConfiguration = $value->getConfiguration();
            $block = $fieldConfiguration->getBlock();
            $fieldType = $block->getBlockType()->getFieldType();

            if (!in_array($value->getValue(), $disabled) && !in_array($fieldType, $disabled)) {
                $this->frontType->setField($fieldType, 'value', $block, $builder, $value, true, true);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactValue::class,
            'website' => null,
            'collection' => [],
            'translation_domain' => 'admin',
        ]);
    }
}
