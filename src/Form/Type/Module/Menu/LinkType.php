<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Menu;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Menu\Link;
use App\Entity\Module\Menu\LinkIntl;
use App\Entity\Module\Menu\LinkMediaRelation;
use App\Form\EventListener\Translation\IntlListener;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
    private Website $website;

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
        $this->website = $this->coreLocator->website()->entity;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        if (!$isNew) {

            $adminName = new WidgetType\AdminNameType($this->coreLocator);
            $adminName->add($builder, [
                'slug' => true,
                'adminNameGroup' => 'col-lg-3',
                'slugGroup' => 'col-lg-3',
            ]);

            $builder->add('pictogram', WidgetType\PictogramType::class, ['attr' => ['data-config' => false]]);

            $builder->add('catalog', EntityType::class, [
                'label' => $this->translator->trans('Catalogue', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'row_attr' => ['class' => 'col-lg-3'],
                'class' => Catalog::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.website = :website')
                        ->setParameter('website', $this->website)
                        ->orderBy('c.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
            ]);
        }

        $saveOptions = [];
        if ($isNew) {
            $saveOptions['class'] = 'px-4';
            $saveOptions['btn_save'] = true;
            $saveOptions['btn_save_label'] = $this->translator->trans('Ajouter au menu', [], 'admin');
        } else {
            $saveOptions['btn_both'] = true;
            $saveOptions['btn_both_label'] = $this->translator->trans('Enregistrer et retourner au menu', [], 'admin');
        }

        $fieldsClass = $isNew ? 'col-12' : 'col-lg-6';
        $checkClass = $isNew ? 'col-12' : 'col-lg-4 my-auto';

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
                    'group' => 'col-lg-3',
                    'data-config' => true,
                ],
            ]);

            if ($this->isInternalUser) {

                $builder->add('color', WidgetType\AppColorType::class, [
                    'attr' => [
                        'data-config' => true,
                        'class' => 'select-icons',
                        'group' => 'col-lg-3',
                    ],
                ]);

                $builder->add('backgroundColor', WidgetType\BackgroundColorSelectType::class, [
                    'attr' => [
                        'data-config' => true,
                        'class' => 'select-icons',
                        'group' => 'col-lg-3',
                    ],
                ]);

                $builder->add('btnColor', WidgetType\ButtonColorType::class, [
                    'label' => $this->translator->trans('Style de bouton', [], 'admin'),
                    'attr' => [
                        'data-config' => true,
                        'class' => 'select-icons',
                        'group' => 'col-lg-3',
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
