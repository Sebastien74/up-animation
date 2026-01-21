<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Entity\Security\Role;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RoleType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RoleType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * RoleType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['adminNameGroup' => 'col-md-6']);

        $builder->add('name', Type\TextType::class, [
            'label' => $this->translator->trans('Code', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un code', [], 'admin'),
                'group' => 'col-md-6',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
