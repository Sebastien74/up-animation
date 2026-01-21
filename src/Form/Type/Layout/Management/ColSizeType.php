<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Layout\Col;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ColSizeType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ColSizeType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ColSizeType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        $limit = 12;
        for ($i = 1; $i <= $limit; ++$i) {
            $choices[$i] = $i;
        }

        $builder->add('size', Type\ChoiceType::class, [
            'label' => $this->translator->trans('Choisissez une taille', [], 'admin'),
            'required' => true,
            'choices' => $choices,
            'display' => 'classic',
            'expanded' => true,
            'row_attr' => ['class' => 'disabled-floating'],
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Ajouter', [], 'admin'),
            'attr' => [
                'class' => 'btn-info d-none edit-element-submit-btn btn-lg disable-preloader',
            ],
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
