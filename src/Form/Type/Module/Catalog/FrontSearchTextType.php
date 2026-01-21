<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FrontSearchTextType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontSearchTextType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FrontSearchTextType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', Type\SearchType::class, [
            'label' => false,
            'required' => false,
            'data' => $options['text'],
            'property_path' => 'text',
            'attr' => [
                'addon' => 'fal search',
                'side' => 'right',
                'placeholder' => $this->translator->trans('Saisissez votre recherche', [], 'front_form'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'text' => null,
            'translation_domain' => 'front_form',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
