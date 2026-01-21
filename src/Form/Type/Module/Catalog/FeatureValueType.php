<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValue;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FeatureValueType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureValueType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;
    private Website $website;

    /**
     * FeatureValueType constructor.
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
        /* @var FeatureValue $data */
        $data = $builder->getData();
        $isNew = !$data->getId();
        $this->website = $options['website'];

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew ? 'col-12' : 'col-md-4',
            'slugGroup' => 'col-md-2',
            'slug-internal' => $this->isInternalUser,
        ]);

        if (!$isNew) {
            $builder->add('catalogfeature', EntityType::class, [
                'label' => $this->translator->trans('Caractéristique', [], 'admin'),
                'class' => Feature::class,
                'attr' => [
                    'group' => 'col-md-3',
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.website = :website')
                        ->setParameter('website', $this->website)
                        ->orderBy('p.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'display' => 'search',
                'constraints' => [new Assert\NotBlank()],
            ]);

            $builder->add('iconClass', WidgetType\IconType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'select-icons',
                    'group' => 'col-md-3',
                ],
            ]);

            $builder->add('featureBeforePost', Type\HiddenType::class, [
                'mapped' => false,
                'data' => $data->getCatalogfeature()->getId(),
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'fields' => ['title'],
            ]);

            $builder->add('catalogs', EntityType::class, [
                'label' => $this->translator->trans('Ajouter par défaut dans les catatogues :', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'class' => Catalog::class,
                'attr' => [
                    'class' => 'catalogs-selector',
                    'group' => 'col-12',
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
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
                'multiple' => true,
            ]);

            $catalogs = $data->getCatalogs();
            if ($catalogs->count() > 0) {
                $catalogsIds = [];
                foreach ($catalogs as $catalog) {
                    $catalogsIds[] = $catalog->getId();
                }
                $builder->add('removeCards', Type\CheckboxType::class, [
                    'required' => false,
                    'mapped' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Supprimer les valeurs des fiches', [], 'admin'),
                    'attr' => ['group' => 'col-md-4', 'class' => 'w-100 remove-cards d-none', 'data-values' => json_encode($catalogsIds)],
                ]);
            }

            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, [
                'data_config' => true,
                'entry_options' => ['onlyMedia' => true],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FeatureValue::class,
            'website' => null,
            'product' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
