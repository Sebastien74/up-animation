<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Layout\Col;
use App\Form\Widget\BackgroundColorType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * BackgroundColorColType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BackgroundColorColType extends AbstractType
{
    private TranslatorInterface $translator;

    private bool $isInternalUser;

    /**
     * BackgroundColorColType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->isInternalUser = $this->authorizationChecker->isGranted('ROLE_INTERNAL');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('backgroundColor', BackgroundColorType::class, [
            'label' => false,
        ]);

        if ($this->isInternalUser) {
            $builder->add('hexadecimalCode', Type\TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Code couleur', [], 'admin'),
                    'class' => 'colorpicker',
                    'group' => 'col-12 mb-3 mt-3',
                ],
            ]);
        }

        $builder->add('backgroundFullSize', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Toute la largeur', [], 'admin'),
            'attr' => [
                'group' => $this->isInternalUser ? 'text-center mb-0' : 'text-center mt-4 mb-0',
                'class' => 'w-100',
            ],
        ]);

        $builder->add('backgroundFullHeight', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Toute la hauteur', [], 'admin'),
            'attr' => [
                'group' => 'text-center mt-2 mb-0',
                'class' => 'w-100',
            ],
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'admin'),
            'attr' => ['class' => 'btn-info edit-element-submit-btn'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Col::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
