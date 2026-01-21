<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\FieldValueIntl;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FieldValueIntlType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FieldValueIntlType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FieldValueIntlType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('introduction', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Label', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un label', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('body', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Valeur', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une valeur', [], 'admin'),
                'group' => 'col-md-4 value-group',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FieldValueIntl::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
