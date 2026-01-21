<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Gallery;

use App\Entity\Core\Website;
use App\Entity\Module\Gallery\Category;
use App\Entity\Module\Gallery\Gallery;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * GalleryType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GalleryType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;
    private Website $website;

    /**
     * GalleryType constructor.
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
        $this->website = $options['website'];

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => !$isNew && $this->isInternalUser ? 'col-md-3' : 'col-md-9',
            'slug-internal' => $this->isInternalUser,
        ]);

        $builder->add('category', EntityType::class, [
            'required' => false,
            'label' => $this->translator->trans('Catégorie', [], 'admin'),
            'display' => 'search',
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'group' => 'col-md-3',
            ],
            'class' => Category::class,
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

        if (!$isNew && $this->isInternalUser) {
            $choices = [];
            for ($x = 1; $x <= 4; ++$x) {
                $choices[$x] = $x;
            }

            $builder->add('nbrColumn', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Nombre de colonne', [], 'admin'),
                'display' => 'search',
                'choices' => $choices,
                'attr' => ['group' => 'col-md-3'],
            ]);
        }

        $builder->add('popup', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Afficher popup au clic des images', [], 'admin'),
            'attr' => ['group' => 'col-md-4', 'class' => 'w-100'],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gallery::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
