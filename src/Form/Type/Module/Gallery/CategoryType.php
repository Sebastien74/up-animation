<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Gallery;

use App\Entity\Module\Gallery\Category;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
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

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew || !$this->isInternalUser ? 'col-12' : 'col-md-9',
        ]);

        if (!$isNew && $this->isInternalUser) {
            $builder->add('itemsPerGallery', Type\IntegerType::class, [
                'label' => $this->translator->trans("Nombre d'images par galerie", [], 'admin'),
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('displayCategory', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher le nom de la catégorie', [], 'admin'),
                'attr' => ['group' => 'col-md-4', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('scrollInfinite', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Scroll infinite', [], 'admin'),
                'attr' => ['group' => 'col-md-4', 'class' => 'w-100', 'data-config' => true],
            ]);
        }

        if (!$isNew) {
            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, [
                'data_config' => true,
                'entry_options' => ['onlyMedia' => true],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['title', 'targetPage'],
                'excludes_fields' => ['targetStyle', 'newTab', 'externalLink'],
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
