<?php

declare(strict_types=1);

namespace App\Form\Type\Seo;

use App\Entity\Seo\Seo;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SeoBasicType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SeoBasicType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * SeoBasicType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('metaTitle', Type\TextType::class, [
            'label' => $this->translator->trans('Méta titre', [], 'admin'),
            'counter' => 60,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                'class' => 'col-12 meta-title refer-code',
            ],
            'required' => false,
        ]);

        $builder->add('metaDescription', Type\TextareaType::class, [
            'label' => $this->translator->trans('Méta description', [], 'admin'),
            'counter' => 155,
            'editor' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez une description', [], 'admin'),
                'class' => 'col-12 meta-description',
            ],
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seo::class,
            'website' => null,
            'have_index_page' => false,
            'translation_domain' => 'admin',
        ]);
    }
}
