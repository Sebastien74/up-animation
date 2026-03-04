<?php

declare(strict_types=1);

namespace App\Form\Type\Information;

use App\Entity\Information\SocialNetwork;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SocialNetworkType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SocialNetworkType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * SocialNetworkType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('facebook', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'facebook',
                'addon-tooltip' => 'Facebook',
            ],
            'row_attr' => ['class' => 'col-lg-6']
        ]);

        $builder->add('twitter', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'twitter',
                'addon-tooltip' => 'Twitter',
            ],
            'row_attr' => ['class' => 'col-lg-6']
        ]);

        $builder->add('google', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'google',
                'addon-tooltip' => 'Google',
            ],
            'row_attr' => ['class' => 'col-lg-6']
        ]);

        $builder->add('youtube', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'youtube',
                'addon-tooltip' => 'Youtube',
            ],
            'row_attr' => ['class' => 'col-lg-6']
        ]);

        $builder->add('tiktok', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'tiktok',
                'addon-tooltip' => 'TikTok',
            ],
            'row_attr' => ['class' => 'col-lg-6']
        ]);

        $builder->add('instagram', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'instagram',
                'addon-tooltip' => 'Instagram',
            ],
            'row_attr' => ['class' => 'col-lg-6']
        ]);

        $builder->add('linkedin', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'linkedin-in',
                'addon-tooltip' => 'Linkedin',
            ],
            'row_attr' => ['class' => 'col-lg-6']
        ]);

        $builder->add('pinterest', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'pinterest',
                'addon-tooltip' => 'Pinterest',
            ],
            'row_attr' => ['class' => 'col-lg-6']
        ]);

        $builder->add('tripadvisor', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'tripadvisor',
                'addon-tooltip' => 'Tripadvisor',
            ],
            'row_attr' => ['class' => 'col-lg-6']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SocialNetwork::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
