<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Core\Configuration;
use App\Entity\Core\Entity;
use App\Entity\Core\Module;
use App\Entity\Layout\BlockType;
use App\Entity\Translation\TranslationDomain;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ConfigurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ConfigurationType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private array $entities = [];

    /**
     * ConfigurationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['isNew'];

        $builder->add('locale', WidgetType\LanguageIconType::class, [
            'label' => $this->translator->trans('Langue par défaut', [], 'admin'),
            'row_attr' => ['class' => $isNew ? 'col-12 col-sm-6 col-lg-4 col-xl-3' : 'col-12'],
        ]);

        if (!$isNew) {

            $builder->add('template', WidgetType\TemplateType::class, [
                'label' => false,
                'display' => false,
            ]);

            $builder->add('buildTheme', WidgetType\BuildThemeType::class);

            $builder->add('cacheExpiration', Type\IntegerType::class, [
                'label' => $this->translator->trans('Durée du cache (en minutes)', [], 'admin'),
                'attr' => [
                    'type' => 'text',
                    'placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin')
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'integer'),
                ],
            ]);

            $builder->add('gdprFrequency', Type\IntegerType::class, [
                'label' => $this->translator->trans('Validité données RGPD (nbr jours)', [], 'admin'),
                'attr' => [
                    'type' => 'text',
                    'placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin')
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type(type: 'integer'),
                ],
            ]);

            $builder->add('onlineStatus', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('En ligne', [], 'admin'),
                'display' => 'switch',
                'row_attr' => ['class' => 'col-12 mb-1'],
                'help' => $this->translator->trans('Site en maintenance si hors ligne', [], 'admin'),
            ]);

            $builder->add('asDefault', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Site principal', [], 'admin'),
                'display' => 'switch',
                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('fullWidth', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Plein écran', [], 'admin'),
                'display' => 'switch',
                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('darkTheme', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Thème sombre', [], 'admin'),
                'display' => 'switch',
                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('progressiveWebApp', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Progressive Web App', [], 'admin'),
                'display' => 'switch',
            ]);

            $builder->add('preloader', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Preloader', [], 'admin'),
                'display' => 'switch',
                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('scrollTopBtn', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Bouton de retour haut de page', [], 'admin'),
                'display' => 'switch',
                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('breadcrumb', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans("Activer les fils d'Ariane", [], 'admin'),
                'display' => 'switch',
                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('subNavigation', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Activer les sous-navigations', [], 'admin'),
                'display' => 'switch',
                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('seoStatus', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Activer le référencement', [], 'admin'),
                'display' => 'switch',
                'help' => $this->translator->trans('A activer uniquement si le site est en production', [], 'admin'),
            ]);

            $builder->add('accessibilityStatus', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans("Activer le module d'accessibilité", [], 'admin'),
                'display' => 'switch',
            ]);

            $builder->add('enableAnalytics', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Activer les statistiques', [], 'admin'),
                'display' => 'switch',
                'help' => $this->translator->trans("Enregistrement des visites et événements anonymes", [], 'admin'),
                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('duplicateMediasStatus', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Activer la duplication des médias', [], 'admin'),
                'display' => 'switch',

                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('mediasSecondary', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Activer 2<sup>ème</sup> image au block média', [], 'admin'),
                'display' => 'switch',

                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('mediasCategoriesStatus', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Activer les catégories de médias', [], 'admin'),
                'display' => 'switch',
            ]);

            $builder->add('collapsedAdminTrees', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans("Empiler les arborescences d'administartion", [], 'admin'),
                'display' => 'switch',
            ]);

            $builder->add('adminAdvertising', Type\CheckboxType::class, [
                'required' => false,
                'label' => $this->translator->trans('Activer la promotion des modules', [], 'admin'),
                'display' => 'switch',

                'row_attr' => ['class' => 'col-12 mb-1'],
            ]);

            $builder->add('locales', WidgetType\LanguageIconType::class, [
                'required' => false,
                'label' => $this->translator->trans('Autres langues', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'multiple' => true,
            ]);

            $builder->add('onlineLocales', WidgetType\LanguageIconType::class, [
                'required' => false,
                'label' => $this->translator->trans('Langues en ligne', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'multiple' => true,
            ]);

            $builder->add('charset', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Charset', [], 'admin'),
                'display' => 'search',
                'choices' => $this->getCharsets(),
            ]);

            $this->getEntities($options);
            $builder->add('transDomains', EntityType::class, [
                'required' => false,
                'label' => $this->translator->trans('Domaines de traduction', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'class' => TranslationDomain::class,
                'choice_label' => function ($entity) {
                    if (preg_match('/\\\\/', $entity->getAdminName()) && !empty($this->entities[$entity->getAdminName()])) {
                        return strip_tags($this->entities[$entity->getAdminName()]);
                    } else {
                        return strip_tags($entity->getAdminName());
                    }
                },
                'display' => 'search',
                'multiple' => true,
            ]);

            $builder->add('emailsDev', WidgetType\TagInputType::class, [
                'label' => $this->translator->trans('E-mails de développement', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Ajouter des e-mails', [], 'admin'),
                ],
            ]);

            $builder->add('emailsSupport', WidgetType\TagInputType::class, [
                'label' => $this->translator->trans('E-mails support Félix', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Ajouter des e-mails', [], 'admin'),
                ],
            ]);

            $builder->add('ipsDev', WidgetType\TagInputType::class, [
                'label' => $this->translator->trans('IPS de développement', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Ajouter des IPS', [], 'admin'),
                ],
            ]);

            $builder->add('ipsCustomer', WidgetType\TagInputType::class, [
                'label' => $this->translator->trans('IPS client', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Ajouter des IPS', [], 'admin'),
                ],
            ]);

            $builder->add('ipsBan', WidgetType\TagInputType::class, [
                'label' => $this->translator->trans('IPS bannies', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Ajouter des IPS', [], 'admin'),
                ],
            ]);

            $builder->add('domains', CollectionType::class, [
                'label' => false,
                'entry_type' => DomainType::class,
                'allow_add' => true,
                'prototype' => true,
                'by_reference' => false,
                'entry_options' => [
                    'attr' => ['class' => 'col-12 domain'],
                    'website_edit' => $options['website_edit'],
                    'website' => $options['website'],
                ],
            ]);

            $builder->add('modulesSearch', Type\TextType::class, [
                'mapped' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Rechercher...', [], 'admin'),
                    'data-target' => '#website_configuration_modules',
                    'data-item' => '.form-check-label',
                ],
            ]);

            $builder->add('modules', EntityType::class, [
                'label' => false,
                'class' => Module::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->orderBy('m.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'by_reference' => false,
                'multiple' => true,
                'expanded' => true,
                'display' => 'switch',
            ]);

            $builder->add('blockTypeSearch', Type\TextType::class, [
                'mapped' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Rechercher...', [], 'admin'),
                    'data-target' => '#website_configuration_blockTypes',
                    'data-item' => '.form-check-label',
                ],
            ]);

            $builder->add('blockTypes', EntityType::class, [
                'label' => false,
                'class' => BlockType::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('b')
                        ->andWhere('b.slug != :slug')
                        ->setParameter('slug', 'core-action')
                        ->orderBy('b.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'multiple' => true,
                'expanded' => true,
                'display' => 'switch',
            ]);
        } else {
            $builder->add('locales', WidgetType\LanguageIconType::class, [
                'required' => false,
                'label' => $this->translator->trans('Autres langues', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'multiple' => true,
            ]);
        }
    }

    /**
     * Get all charsets.
     */
    private function getCharsets(): array
    {
        $charsetsList = mb_list_encodings();
        sort($charsetsList);
        $charsets = [];
        foreach ($charsetsList as $charset) {
            $charsets[$charset] = $charset;
        }
        return $charsets;
    }

    /**
     * Get WebsiteModel entities.
     */
    private function getEntities(array $options = []): void
    {
        $entities = $this->entityManager->getRepository(Entity::class)->findBy(['website' => $options['website']]);
        foreach ($entities as $entity) {
            /** @var $entity Entity */
            if ($entity->getAdminName()) {
                $this->entities[$entity->getClassName()] = $entity->getAdminName();
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
            'website' => null,
            'website_edit' => null,
            'isNew' => false,
            'translation_domain' => 'admin',
        ]);
    }
}
