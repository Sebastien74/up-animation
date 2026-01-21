<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Newscast;

use App\Entity\Core\Website;
use App\Entity\Module\Newscast\Category;
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
 * CategoryType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;
    private Website $website;

    /**
     * CategoryType constructor.
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
            'adminNameGroup' => $isNew ? 'col-12' : ($this->isInternalUser ? 'col-md-6' : 'col-12'),
            'slug-internal' => $this->isInternalUser,
        ]);

        if (!$isNew && $this->isInternalUser) {
            $builder->add('categoryTemplate', EntityType::class, [
                'required' => false,
                'class' => Category::class,
                'label' => $this->translator->trans('Template', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => [
                    'group' => 'col-md-3',
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.website = :website')
                        ->setParameter('website', $this->website)
                        ->orderBy('c.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'display' => 'search',
            ]);

            $builder->add('icon', WidgetType\IconType::class, [
                'attr' => [
                    'class' => 'select-icons',
                    'group' => 'col-md-4',
                    'data-config' => true,
                ],
            ]);

            $builder->add('color', WidgetType\AppColorType::class, [
                'label' => $this->translator->trans('Couleur', [], 'admin'),
                'attr' => [
                    'data-config' => true,
                    'class' => 'select-icons',
                    'group' => 'col-md-4',
                ],
            ]);

            $builder->add('orderBy', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Ordonner les actualités par', [], 'admin'),
                'display' => 'search',
                'attr' => ['group' => 'col-md-4', 'data-config' => true],
                'choices' => [
                    $this->translator->trans('Dates (croissantes)', [], 'admin') => 'publicationStart-asc',
                    $this->translator->trans('Dates (décroissantes)', [], 'admin') => 'publicationStart-desc',
                    $this->translator->trans('Positions (croissantes)', [], 'admin') => 'position-asc',
                    $this->translator->trans('Positions (décroissantes)', [], 'admin') => 'position-desc',
                ],
            ]);

            $builder->add('formatDate', WidgetType\FormatDateType::class, [
                'attr' => ['group' => 'col-md-4', 'data-config' => true],
            ]);

            $builder->add('itemsPerPage', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans("Nombre d'actualités par page", [], 'admin'),
                'attr' => ['group' => 'col-md-4', 'data-config' => true],
            ]);

            $builder->add('useDefaultTemplate', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Utiliser le template de la catégorie principale', [], 'admin'),
                'attr' => ['group' => 'col-md-4 d-flex align-items-end', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('hideDate', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Cacher la date', [], 'admin'),
                'attr' => ['group' => 'col-md-4 d-flex align-items-end', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('displayCategory', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher le nom de la catégorie', [], 'admin'),
                'attr' => ['group' => 'col-md-4', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('asDefault', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Catégorie principale', [], 'admin'),
                'attr' => ['group' => 'col-md-4', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('mainMediaInHeader', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans("Afficher l'image principale dans les entêtes", [], 'admin'),
                'attr' => ['group' => 'col-md-4', 'class' => 'w-100', 'data-config' => true],
            ]);
        }

        if (!$isNew) {

            $builder->add('asEvents', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans("Type événement", [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, [
                'data_config' => true,
                'entry_options' => ['onlyMedia' => true],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['title' => 'col-12', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3', 'targetLabel' => 'col-md-3'],
                'label_fields' => [
                    'placeholder' => $this->translator->trans('Label du lien voir tout', [], 'admin'),
                    'help' => $this->translator->trans('Label du lien en savoir plus', [], 'admin'),
                    'error' => $this->translator->trans('Label de la date de publication', [], 'admin'),
                    'targetLabel' => $this->translator->trans('Label du lien de retour', [], 'admin'),
                ],
                'target_config' => false,
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
