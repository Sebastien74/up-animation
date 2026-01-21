<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PositionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PositionType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * PositionType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        for ($x = 1; $x <= $options['iterations']; ++$x) {
            $choices[$x] = $x;
        }

        $builder->add('position', Type\ChoiceType::class, [
            'label' => false,
            'display' => 'search',
            'choices' => $choices,
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'admin'),
            'attr' => ['class' => 'btn-info'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'old_position' => null,
            'iterations' => 0,
            'translation_domain' => 'admin',
        ]);
    }
}
