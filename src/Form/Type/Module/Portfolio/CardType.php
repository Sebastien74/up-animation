<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Portfolio;

use App\Entity\Module\Portfolio\Card;
use App\Entity\Module\Portfolio\Category;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CardType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CardType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isLayoutUser;

    /**
     * CardType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isLayoutUser = $user && in_array('ROLE_LAYOUT_PORTFOLIOCARD', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Card $data */
        $card = $builder->getData();
        $isNew = !$card->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew ? 'col-12' : 'col-md-10',
        ]);

        if ($isNew && $this->isLayoutUser) {
            $builder->add('customLayout', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Template personnalisé', [], 'admin'),
                'attr' => ['group' => 'col-md-4', 'class' => 'w-100', 'data-config' => true],
            ]);
        }

        if (!$isNew) {
            $urls = new WidgetType\UrlsCollectionType($this->coreLocator);
            $urls->add($builder, ['display_seo' => true]);

            $dates = new WidgetType\PublicationDatesType($this->coreLocator);
            $dates->add($builder);

            $builder->add('promote', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Mettre en avant', [], 'admin'),
                'attr' => ['group' => 'col-md-2 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('categories', EntityType::class, [
                'label' => $this->translator->trans('Catégories', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'class' => Category::class,
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'multiple' => true,
            ]);

            if (!$card->isCustomLayout()) {
                $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
                $intls->add($builder, [
                    'website' => $options['website'],
                    'fields' => ['title' => 'col-md-6', 'subTitle' => 'col-md-4', 'introduction', 'body', 'video', 'targetLink' => 'col-md-12 add-title', 'targetPage' => 'col-md-4', 'targetLabel' => 'col-md-4', 'targetStyle' => 'col-md-4', 'newTab' => 'col-md-4'],
                ]);
            }

            if ($this->isLayoutUser) {
                $builder->add('customLayout', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Template personnalisé', [], 'admin'),
                    'attr' => ['group' => 'col-md-4', 'class' => 'w-100'],
                ]);
            }
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Card::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
