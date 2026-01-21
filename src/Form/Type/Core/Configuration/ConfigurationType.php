<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Configuration;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Form\Type\Layout\Management\CssClassType;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
    private Website $website;

    /**
     * ConfigurationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->website = $options['website'];

        $mediaRelation = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
        $mediaRelation->add($builder, ['entry_options' => ['onlyMedia' => true, 'onlyLocaleMedias' => true, 'active' => true]]);

        $builder->add('colors', CollectionType::class, [
            'label' => false,
            'entry_type' => ColorType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => ['class' => 'color'],
                'website' => $options['website'],
            ],
        ]);

        $builder->add('transitions', CollectionType::class, [
            'label' => false,
            'entry_type' => TransitionType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => [
                    'class' => 'transition',
                    'icon' => 'fal hurricane',
                    'group' => 'col-md-4',
                    'caption' => $this->translator->trans('Transitions', [], 'admin'),
                    'button' => $this->translator->trans('Ajouter une transition', [], 'admin'),
                ],
                'website' => $options['website'],
            ],
        ]);

        $builder->add('cssClasses', CollectionType::class, [
            'label' => false,
            'entry_type' => CssClassType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => ['class' => 'cssclass'],
                'website' => $options['website'],
            ],
        ]);

        $builder->add('pages', EntityType::class, [
            'required' => false,
            'label' => $this->translator->trans('Pages principales', [], 'admin'),
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            ],
            'class' => Page::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('p')
                    ->leftJoin('p.urls', 'u')
                    ->andWhere('p.website = :website')
                    ->andWhere('p.deletable = :deletable')
                    ->andWhere('u.archived = :archived')
                    ->setParameter('website', $this->website)
                    ->setParameter('deletable', true)
                    ->setParameter('archived', false)
                    ->addSelect('u')
                    ->orderBy('p.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'display' => 'search',
            'multiple' => true,
        ]);

        $builder->add('website', WebsiteType::class, [
            'label' => false,
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_save' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
