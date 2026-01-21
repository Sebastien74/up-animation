<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Api\CustomIntl;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CustomIntlType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CustomIntlType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CustomIntlType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('headScript', Type\TextareaType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Script (head)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Ajouter le script', [], 'admin'),
                'group' => 'col-md-6',
            ],
        ]);

        $builder->add('topBodyScript', Type\TextareaType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Script (Top body)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Ajouter le script', [], 'admin'),
                'group' => 'col-md-6',
            ],
        ]);

        $builder->add('bottomBodyScript', Type\TextareaType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Script (Bottom body)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Ajouter le script', [], 'admin'),
                'group' => 'col-md-6',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomIntl::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
