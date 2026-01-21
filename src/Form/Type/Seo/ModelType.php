<?php

declare(strict_types=1);

namespace App\Form\Type\Seo;

use App\Entity\Seo\Model;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ModelType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ModelType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ModelType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('metaTitle', Type\TextType::class, [
            'label' => $this->translator->trans('Méta titre', [], 'admin'),
            'counter' => 60,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                'class' => 'meta-title',
            ],
            'required' => false,
        ]);

        $builder->add('metaTitleSecond', Type\TextType::class, [
            'label' => $this->translator->trans('Méta titre (après le tiret)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                'class' => 'meta-title-second',
            ],
            'required' => false,
        ]);

        $builder->add('metaDescription', Type\TextareaType::class, [
            'label' => $this->translator->trans('Méta description', [], 'admin'),
            'counter' => 155,
            'editor' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez une description', [], 'admin'),
                'class' => 'meta-description',
            ],
            'required' => false,
        ]);

        $builder->add('footerDescription', Type\TextareaType::class, [
            'label' => $this->translator->trans('Description pied de page', [], 'admin'),
            'editor' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez une description', [], 'admin'),
                'class' => 'footer-description',
            ],
            'required' => false,
        ]);

        $builder->add('noAfterDash', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Désactiver après tiret', [], 'admin'),
            'attr' => ['group' => 'col-12', 'class' => 'w-100'],
        ]);

        $builder->add('metaOgTitle', Type\TextType::class, [
            'label' => $this->translator->trans('OG Méta titre', [], 'admin'),
            'counter' => 60,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                'class' => 'meta-og-title',
            ],
            'required' => false,
        ]);

        $builder->add('metaOgDescription', Type\TextareaType::class, [
            'label' => $this->translator->trans('OG Méta description', [], 'admin'),
            'counter' => 155,
            'editor' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez une description', [], 'admin'),
                'class' => 'meta-og-description',
            ],
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Model::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
