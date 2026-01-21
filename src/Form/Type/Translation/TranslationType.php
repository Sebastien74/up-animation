<?php

declare(strict_types=1);

namespace App\Form\Type\Translation;

use App\Entity\Translation\Translation;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TranslationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TranslationType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * TranslationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', Type\TextType::class, [
            'required' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un contenu', [], 'admin'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Translation::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
