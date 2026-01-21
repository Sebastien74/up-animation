<?php

declare(strict_types=1);

namespace App\Form\Type\Seo\Configuration;

use App\Entity\Seo\SeoConfiguration;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ConfigurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ConfigurationType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ConfigurationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('website', WebsiteType::class, [
            'label' => false,
        ]);

        $builder->add('disabledIps', WidgetType\TagInputType::class, [
            'label' => $this->translator->trans('Désactiver IPS', [], 'admin'),
            'required' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Ajouter des IPS', [], 'admin'),
            ],
        ]);

        $builder->add('microData', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Activer les micros données', [], 'admin'),
            'attr' => ['group' => 'col-md-6 d-flex align-items-end', 'class' => 'w-100'],
        ]);

        $builder->add('disableAfterDash', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Retirer toutes les métas après le tiret', [], 'admin'),
            'attr' => ['group' => 'col-md-6 d-flex align-items-end', 'class' => 'w-100'],
        ]);

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'author', 'authorType', 'introduction'],
            'label_fields' => [
                'title' => $this->translator->trans('Méta titre par défault (après le tiret)', [], 'admin'),
                'placeholder' => $this->translator->trans('Type de site (Microdata)', [], 'admin'),
                'author' => $this->translator->trans('Auteur (Microdata)', [], 'admin'),
                'authorType' => $this->translator->trans("Type d'auteur (Microdata)", [], 'admin'),
                'introduction' => $this->translator->trans('Description (Microdata)', [], 'admin'),
            ],
            'placeholder_fields' => [
                'placeholder' => $this->translator->trans('Saisissez un type', [], 'admin'),
            ],
            'fields_type' => [
                'introduction' => Type\TextType::class,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SeoConfiguration::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
