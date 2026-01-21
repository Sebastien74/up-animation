<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Search;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FrontType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FrontType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('search', TextType::class, [
            'label' => false,
            'data' => $options['field_data'],
            'attr' => [
                'class' => 'border-primary',
                'placeholder' => $this->translator->trans('Saisissez votre recherche', [], 'front_form'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'field_data' => null,
            'csrf_protection' => false,
            'data_class' => null,
            'website' => null,
            'translation_domain' => 'front_form',
        ]);
    }

    public function getBlockPrefix(): ?string
    {
        return '';
    }
}
