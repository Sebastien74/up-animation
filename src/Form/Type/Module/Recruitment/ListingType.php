<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Recruitment;

use App\Entity\Module\Recruitment\Listing;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use App\Form\Widget as WidgetType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ListingType
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class ListingType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * ListingType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $this->isInternalUser && !$isNew ? 'col-md-5' : 'col-12',
            'slugGroup' => 'col-md-2',
            'slug-internal' => $this->isInternalUser && !$isNew,
        ]);

        if (!$isNew) {

            $builder->add('orderBy', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Ordonner par', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Position', [], 'admin') => 'position',
                    $this->translator->trans('Titre', [], 'admin') => 'title',
                    $this->translator->trans('Date de création', [], 'admin') => 'createdAt',
                    $this->translator->trans('Date de début de publication', [], 'admin') => 'publicationStart',
                    $this->translator->trans('Aléatoire', [], 'admin') => 'random',
                ],
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('orderSort', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Trier par ordre', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Croissant', [], 'admin') => 'ASC',
                    $this->translator->trans('Décroissant', [], 'admin') => 'DESC',
                ],
                'attr' => ['group' => 'col-md-2'],
            ]);

            $builder->add('displayPromote', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher les offres mises en avant', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('displayFilters', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher les filtres', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Listing::class,
			'website' => null,
			'translation_domain' => 'admin'
		]);
	}
}