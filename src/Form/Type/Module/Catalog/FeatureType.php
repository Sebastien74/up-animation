<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Feature;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FeatureType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;
    private Website $website;

    /**
     * FeatureType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* @var Feature $data */
        $data = $builder->getData();
        $isNew = !$data->getId();
        $this->website = $options['website'];

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['slug-internal' => $this->isInternalUser, 'adminNameGroup' => $isNew ? 'col-md-9' : 'col-md-6']);

        if (!$isNew) {
            $builder->add('iconClass', WidgetType\IconType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'select-icons',
                    'group' => 'col-md-3',
                ],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'fields' => ['title'],
            ]);

            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, [
                'data_config' => true,
                'entry_options' => ['onlyMedia' => true],
            ]);

            $builder->add('catalogs', EntityType::class, [
                'label' => $this->translator->trans('Ajouter par défaut dans les catatogues :', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'class' => Catalog::class,
                'attr' => [
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
                $builder->add('removeCards', CheckboxType::class, [
                    'required' => false,
                    'mapped' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Supprimer les caractéristiques des fiches', [], 'admin'),
                    'attr' => ['group' => 'col-md-4', 'class' => 'w-100 remove-cards d-none', 'data-values' => json_encode($catalogsIds)],
                ]);
            }
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feature::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
