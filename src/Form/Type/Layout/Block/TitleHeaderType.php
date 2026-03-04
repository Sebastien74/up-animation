<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * TitleHeaderType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TitleHeaderType extends AbstractType
{
    private const bool ACTIVE_LARGE = false;
    private bool $isInternalUser;

    /**
     * TitleHeaderType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        if ($this->isInternalUser) {
            $builder->add('backgroundColor', WidgetType\BackgroundColorSelectType::class, [
                'attr' => ['class' => 'col-12 select-icons',
                    'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            ]);

            $builder->add('color', WidgetType\AppColorType::class, [
                'attr' => ['class' => 'col-12 select-icons',
                    'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            ]);
        }

        if (self::ACTIVE_LARGE) {
            $builder->add('large', CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->coreLocator->translator()->trans('Grande entête', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100'],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);
        }

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => ['title' => 'col-md-5', 'subTitle' => 'col-md-5', 'body'],
            'fields_data' => ['titleForce' => 1],
            'title_force' => true,
        ]);

        $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
        $mediaRelations->add($builder, ['entry_options' => [
            'onlyMedia' => true,
        ]]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_back' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
            'translation_domain' => 'admin',
            'website' => null,
        ]);
    }
}
