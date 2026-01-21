<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Api\Api;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ApiType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ApiType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ApiType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('securitySecretKey', Type\TextType::class, [
            'required' => false,
            'bytes' => true,
            'label' => $this->translator->trans('Clé privée', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez la clé', [], 'admin'),
                'group' => 'col-md-6',
            ],
        ]);

        $builder->add('securitySecretIv', Type\TextType::class, [
            'required' => false,
            'bytes' => true,
            'label' => $this->translator->trans('Clé de décryptage', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez la clé', [], 'admin'),
                'group' => 'col-md-6',
            ],
        ]);

        $builder->add('google', GoogleType::class, [
            'label' => false,
        ]);

        $builder->add('instagram', InstagramType::class, [
            'label' => false,
        ]);

        $builder->add('addThis', Type\TextareaType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('AddThis script', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Ajouter le script', [], 'admin'),
                'group' => 'col-md-6',
            ],
        ]);

        $builder->add('custom', CustomType::class, [
            'label' => false,
        ]);

        $builder->add('tawkToId', Type\UrlType::class, [
            'required' => false,
            'label' => $this->translator->trans('TawkTo URL', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'group' => 'col-md-6',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Api::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
