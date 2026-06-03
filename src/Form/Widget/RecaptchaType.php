<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RecaptchaType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RecaptchaType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * RecaptchaType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * Add fields.
     */
    public function add(FormBuilderInterface $builder, mixed $entity = null, bool $activeRecaptcha = false): void
    {
        $entity = ($entity && method_exists($entity, 'isRecaptcha') && method_exists($entity, 'getConfiguration')) ? $entity->getConfiguration() : $entity;

        if (($entity && method_exists($entity, 'isRecaptcha') && $entity->isRecaptcha()) || $activeRecaptcha) {

            $builder->add('field_ho', Type\TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'attr' => [
                    'class' => 'col-12 form-field-none field_ho',
                    'autocomplete' => 'off',
                    'tabindex' => '-1',
                    'aria-hidden' => 'true',
                ],
                'row_attr' => ['class' => 'col-12 mb-0 form-field-none', 'aria-hidden' => 'true'],
            ]);

            $builder->add('field_ho_entitled', Type\TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'attr' => [
                    'class' => 'col-12 form-field-none',
                    'autocomplete' => 'off',
                    'tabindex' => '-1',
                    'aria-hidden' => 'true',
                ],
                'row_attr' => ['class' => 'col-12 mb-0 form-field-none', 'aria-hidden' => 'true'],
            ]);
        }
    }
}
