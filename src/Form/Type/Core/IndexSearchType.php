<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * IndexSearchType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IndexSearchType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * IndexSearchType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('search', TextType::class, [
            'required' => false,
            'attr' => ['placeholder' => $this->translator->trans('Rechercher', [], 'admin')],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin',
            'fields' => ['adminName', 'position'],
            'interface' => [],
            'website' => null,
            'data_class' => null,
            'block_name' => 'search',
            'csrf_protection' => false,
        ]);
    }
}
