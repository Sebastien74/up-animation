<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Form\Model\Security\Admin\PasswordResetModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PasswordResetType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PasswordResetType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * PasswordResetType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', Type\RepeatedType::class, [
            'label' => false,
            'type' => Type\PasswordType::class,
            'invalid_message' => $this->translator->trans('Les mots de passe sont différents', [], 'validators_cms'),
            'first_options' => [
                'label' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un mot de passe', [], 'security_cms'),
                    'group' => 'col-12 mb-3',
                    'class' => 'pt-2 pb-2 password-checker',
                ],
            ],
            'second_options' => [
                'label' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Confirmez le mot de passe', [], 'security_cms'),
                    'group' => 'col-12 mb-3',
                    'class' => 'pt-2 pb-2',
                ],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PasswordResetModel::class,
            'website' => null,
        ]);
    }
}
