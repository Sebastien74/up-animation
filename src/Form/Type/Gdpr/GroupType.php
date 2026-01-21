<?php

declare(strict_types=1);

namespace App\Form\Type\Gdpr;

use App\Entity\Gdpr\Group;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * GroupType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GroupType extends AbstractType
{
    private TranslatorInterface $translator;
    /**
     * GroupType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder);

        if (!$isNew) {
            $builder->add('script', Type\TextareaType::class, [
                'required' => false,
                'editor' => false,
                'label' => $this->translator->trans('Script', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Éditer le script', [], 'admin'),
                ],
            ]);

            $builder->add('scriptInHead', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Script dans le <head>', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('active', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer', [], 'admin'),
                'attr' => ['group' => 'col-md-2', 'class' => 'w-100'],
            ]);

            $builder->add('anonymize', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Anonymiser le script', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['title', 'introduction' => 'col-12 editor', 'body', 'targetLink'],
                'target_config' => false,
            ]);

            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, ['data_config' => true]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
