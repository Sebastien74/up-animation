<?php

declare(strict_types=1);

namespace App\Form\Type\Seo\Configuration;

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
        $builder->add('matomoId', Type\TextType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Matomo ID', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un id', [], 'admin'),
                'group' => 'col-12',
            ],
        ]);

        $builder->add('matomoUrl', Type\TextType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Matomo URL (Sans protocol)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le nom du cookie', [], 'admin'),
                'group' => 'col-12',
            ],
        ]);

        $builder->add('headScriptSeo', Type\TextareaType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Script (head)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Ajouter le script', [], 'admin'),
                'group' => 'col-12',
            ],
        ]);

        $builder->add('topBodyScriptSeo', Type\TextareaType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Script (Top body)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Ajouter le script', [], 'admin'),
                'group' => 'col-md-6',
            ],
        ]);

        $builder->add('bottomBodyScriptSeo', Type\TextareaType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Script (Bottom body)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Ajouter le script', [], 'admin'),
                'group' => 'col-md-6',
            ],
        ]);

        $builder->add('axeptioId', Type\TextType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Axeptio ID', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un id', [], 'admin'),
                'group' => 'col-12',
            ],
        ]);

        $builder->add('axeptioCookieVersion', Type\TextType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Axeptio Cookie Version', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le nom du cookie', [], 'admin'),
                'group' => 'col-12',
            ],
        ]);

        $builder->add('axeptioExternal', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Activer Axeptio GTM', [], 'admin'),
            'attr' => ['group' => 'col-12 d-flex align-items-end', 'class' => 'w-100'],
        ]);

        $builder->add('aiFelixSiteId', Type\TextType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Site ID', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un id', [], 'admin'),
                'group' => 'col-12',
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
