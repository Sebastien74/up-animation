<?php

declare(strict_types=1);

namespace App\Form\Type\Media;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SearchType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SearchType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * SearchType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('searchMedia', Type\SearchType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Rechercher', [], 'admin'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'admin',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
