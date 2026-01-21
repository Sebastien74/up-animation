<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Menu;

use App\Entity\Module\Menu\Link;
use App\Entity\Module\Menu\LinkIntl;
use App\Entity\Module\Menu\LinkMediaRelation;
use App\Form\EventListener\Translation\IntlListener;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LinkType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LinkType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * LinkType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        if (!$isNew) {

            $adminName = new WidgetType\AdminNameType($this->coreLocator);
            $adminName->add($builder, [
                'slug' => true,
                'adminNameGroup' => 'col-md-6',
                'slugGroup' => 'col-md-3',
            ]);

            $builder->add('pictogram', WidgetType\PictogramType::class, ['attr' => ['data-config' => false]]);
        }

        $saveOptions = [];
        if ($isNew) {
            $saveOptions['class'] = 'px-4';
            $saveOptions['btn_save'] = true;
            $saveOptions['btn_save_label'] = $this->translator->trans('Ajouter au menu', [], 'admin');
        } else {
            $saveOptions['btn_back'] = true;
        }

        $fieldsClass = $isNew ? 'col-12' : 'col-md-6';
        $checkClass = $isNew ? 'col-12' : 'col-md-4 my-auto';

        $builder->add('intl', WidgetType\IntlType::class, [
            'label' => false,
            'data_class' => LinkIntl::class,
            'title_force' => false,
            'fields' => ['title' => $fieldsClass, 'subTitle' => $fieldsClass, 'introduction' => 'col-12', 'targetLink' => $fieldsClass, 'targetPage' => $fieldsClass, 'newTab' => $checkClass],
            'excludes_fields' => ['targetStyle'],
            'label_fields' => [
                'subTitle' => $this->translator->trans('Titre du sous-menu', [], 'admin'),
                'introduction' => $this->translator->trans('Description', [], 'admin'),
            ],
            'placeholder_fields' => [
                'subTitle' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                'introduction' => $this->translator->trans('Saisissez une description', [], 'admin'),
            ],
            'required_fields' => ['title'],
            'row_attr' => ['class' => $isNew ? 'px-4' : ''],
        ])->addEventSubscriber(new IntlListener($this->coreLocator));

        if (!$isNew) {

            $builder->add('mediaRelation', WidgetType\MediaRelationType::class, [
                'onlyMedia' => true,
                'data_class' => LinkMediaRelation::class,
                'attr' => [
                    'data-config' => true,
                    'group' => 'col-12',
                ],
            ]);

            $builder->add('icon', WidgetType\IconType::class, [
                'attr' => [
                    'class' => 'select-icons',
                    'group' => 'col-md-3',
                    'data-config' => true,
                ],
            ]);

            if ($this->isInternalUser) {
                $builder->add('color', WidgetType\AppColorType::class, [
                    'attr' => [
                        'data-config' => true,
                        'class' => 'select-icons',
                        'group' => 'col-md-3',
                    ],
                ]);

                $builder->add('backgroundColor', WidgetType\BackgroundColorSelectType::class, [
                    'attr' => [
                        'data-config' => true,
                        'class' => 'select-icons',
                        'group' => 'col-md-3',
                    ],
                ]);

                $builder->add('btnColor', WidgetType\ButtonColorType::class, [
                    'label' => $this->translator->trans('Style de bouton', [], 'admin'),
                    'attr' => [
                        'data-config' => true,
                        'class' => 'select-icons',
                        'group' => 'col-md-3',
                    ],
                ]);
            }

            $save = new WidgetType\SubmitType($this->coreLocator);
            $save->add($builder, $saveOptions);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Link::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
