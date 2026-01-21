<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Module\Catalog\Catalog;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CatalogType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CatalogType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * CatalogType constructor.
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

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['slug-internal' => $this->isInternalUser]);

        if (!$isNew) {
            if ($this->isInternalUser) {
                $builder->add('tabs', Type\ChoiceType::class, [
                    'label' => $this->translator->trans('Onglets & champs spécifiques Back-office', [], 'admin'),
                    'choices' => [
                        $this->translator->trans('Description', [], 'admin') => 'intls',
                        $this->translator->trans('Catégories', [], 'admin') => 'categories',
                        $this->translator->trans('Caractéristiques', [], 'admin') => 'features',
                        $this->translator->trans('Configuration', [], 'admin') => 'configuration',
                        $this->translator->trans('Produits associés', [], 'admin') => 'products',
                        $this->translator->trans('Référencement', [], 'admin') => 'seo',
                        $this->translator->trans('Coordonnées', [], 'admin') => 'informations',
                        $this->translator->trans('Lots', [], 'admin') => 'lots',
                        $this->translator->trans('Dates', [], 'admin') => 'dates',
                        $this->translator->trans('Sous-catégories', [], 'admin') => 'sub-categories',
                        $this->translator->trans('Médias', [], 'admin') => 'medias',
                    ],
                    'multiple' => true,
                    'display' => 'search',
                    'attr' => ['data-config' => true],
                ]);

                $builder->add('formatDate', WidgetType\FormatDateType::class, [
                    'attr' => ['group' => 'col-md-4', 'data-config' => true],
                ]);
            }

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'fields' => ['title' => 'col-12', 'placeholder' => 'col-md-4', 'help' => 'col-md-4', 'targetLabel' => 'col-md-4'],
                'label_fields' => [
                    'placeholder' => $this->translator->trans('Label du lien voir tout', [], 'admin'),
                    'help' => $this->translator->trans('Label du lien en savoir plus', [], 'admin'),
                    'targetLabel' => $this->translator->trans('Label du lien de retour', [], 'admin'),
                ],
                'target_config' => false,
                'disableTitle' => true,
            ]);

            $builder->add('layout', WidgetType\LayoutType::class, [
                'row_attr' => ['class'=> 'px-0']
            ]);

            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, [
                'data_config' => true,
                'entry_options' => ['onlyMedia' => true],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Catalog::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
