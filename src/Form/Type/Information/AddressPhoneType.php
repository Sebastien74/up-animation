<?php

declare(strict_types=1);

namespace App\Form\Type\Information;

use App\Entity\Information\Phone;
use App\Form\Validator;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AddressPhoneType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AddressPhoneType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AddressPhoneType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('number', Type\TextType::class, [
            'label' => $this->translator->trans('Numéro de téléphone', [], 'admin'),
            'required' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
                'group' => 'col-md-4',
            ],
            'constraints' => [new Validator\Phone()],
        ]);

        $builder->add('tagNumber', Type\TextType::class, [
            'label' => $this->translator->trans('Numéro de téléphone (href)', [], 'admin'),
            'required' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
                'group' => 'col-md-4',
            ],
            'constraints' => [new Validator\Phone()],
        ]);

        $builder->add('type', Type\ChoiceType::class, [
            'label' => $this->translator->trans('Type', [], 'admin'),
            'display' => 'search',
            'choices' => [
                $this->translator->trans('Fixe', [], 'admin') => 'fixe',
                $this->translator->trans('Portable', [], 'admin') => 'mobile',
                $this->translator->trans('Fax', [], 'admin') => 'fax',
            ],
            'attr' => [
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'group' => 'col-md-4',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Phone::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
