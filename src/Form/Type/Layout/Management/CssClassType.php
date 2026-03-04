<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Layout\CssClass;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CssClassType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CssClassType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CssClassType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', Type\TextType::class, [
            'label' => $this->translator->trans('Nom', [], 'admin'),
            'attr' => ['placeholder' => $this->translator->trans('Saisissez une classe', [],
                 'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('description', Type\TextType::class, [
            'label' => $this->translator->trans('Description', [], 'admin'),
            'required' => false,
            'attr' => ['placeholder' => $this->translator->trans('Saisissez une description', [],
                 'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-8'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CssClass::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
