<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use App\Form\Validator;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SupportType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SupportType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * SupportType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Type\TextType::class, [
            'label' => $this->translator->trans('Nom & prénom', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre nom & prénom', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('phone', Type\TelType::class, [
            'label' => $this->translator->trans('Numéro de téléphone', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre numéro de téléphone', [], 'admin'),
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Validator\Phone(),
            ],
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => $this->translator->trans('E-mail', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre e-mail', [], 'admin'),
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Email(),
            ],
        ]);

        $builder->add('message', Type\TextareaType::class, [
            'label' => $this->translator->trans('Message', [], 'admin'),
            'editor' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez votre message', [], 'admin'),
            ],
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Envoyer', [], 'admin'),
            'attr' => [
                'class' => 'btn btn-info w-100',
                'group' => 'mb-0',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
