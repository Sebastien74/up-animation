<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Front;

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
 * PhoneFrontType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PhoneFrontType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * PhoneFrontType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('number', Type\TextType::class, [
            'label' => $this->translator->trans('Numéro de téléphone', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Validator\Phone(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Phone::class,
            'website' => null,
            'translation_domain' => 'front',
        ]);
    }
}
