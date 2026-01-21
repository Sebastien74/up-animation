<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Layout\GridCol;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * GridColType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GridColType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * GridColType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('position', Type\IntegerType::class, [
            'label' => $this->translator->trans('Position', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une position', [], 'admin'),
                'group' => 'col-md-6',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $choices = [];
        for ($i = 1; $i <= 12; ++$i) {
            $choices[$i] = $i;
        }
        $builder->add('size', Type\ChoiceType::class, [
            'label' => $this->translator->trans('Taille', [], 'admin'),
            'display' => 'search',
            'attr' => ['group' => 'col-md-6'],
            'choices' => $choices,
            'constraints' => [new Assert\NotBlank()],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GridCol::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
