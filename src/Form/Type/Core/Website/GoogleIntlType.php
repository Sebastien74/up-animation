<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Api\GoogleIntl;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * GoogleIntlType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GoogleIntlType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * GoogleIntlType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('analyticsUa', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('User agent', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le UA', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('tagManagerKey', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Tag manager key', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez la clé', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('searchConsoleKey', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Search console key', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez la clé', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('clientId', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Client id', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un id', [], 'admin'),
                'group' => 'col-md-8',
            ],
            'help' => $this->translator->trans('Create ID client AUTH identification in API account. Ex: xxxxxxxxxxxx-xxxxxxxxxx.apps.googleusercontent.com', [], 'admin'),
        ]);

        $builder->add('analyticsAccountId', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Analytics account id', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un id', [], 'admin'),
                'group' => 'col-md-4',
            ],
            'help' => $this->translator->trans('Create ID client AUTH identification in API account and activate Analytics API. ga:xxxxxxx', [], 'admin'),
        ]);

        $builder->add('tagManagerLayer', Type\TextareaType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Data layer script', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez le dataLayer', [], 'admin'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GoogleIntl::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
