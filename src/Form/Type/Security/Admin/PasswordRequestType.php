<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PasswordRequestType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PasswordRequestType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * PasswordRequestType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', Type\EmailType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre e-mail', [], 'security_cms'),
                'class' => 'pt-2 pb-2',
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Email(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
        ]);
    }
}
