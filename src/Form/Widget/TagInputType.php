<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Form\DataTransformer\ArrayToStringTransformer;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TagInputType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TagInputType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * TagInputType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ArrayToStringTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'role' => 'tagsinput',
            'label_attr' => [
                'class' => 'w-100',
            ],
            'attr' => [
                'placeholder' => $this->translator->trans('Ajoutez', [], 'admin'),
            ],
        ]);
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
