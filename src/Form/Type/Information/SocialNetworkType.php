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
            ],
        ]);

        $builder->add('twitter', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'twitter',
            ],
        ]);

        $builder->add('google', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'google',
            ],
        ]);

        $builder->add('youtube', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'youtube',
            ],
        ]);

        $builder->add('tiktok', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'tiktok',
            ],
        ]);

        $builder->add('instagram', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'instagram',
            ],
        ]);

        $builder->add('linkedin', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'linkedin-in',
            ],
        ]);

        $builder->add('pinterest', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'pinterest-p',
            ],
        ]);

        $builder->add('tripadvisor', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                'addon' => 'tripadvisor',
            ],
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
