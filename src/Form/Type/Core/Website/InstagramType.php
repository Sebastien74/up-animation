<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Api\Instagram;
use App\Service\Content\Feed\InstagramService;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * InstagramType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InstagramType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ApiType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly InstagramService $instagramService
    ) {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('appId', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('App ID', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez l\'App ID Instagram', [], 'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        $builder->add('appSecret', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('App Secret', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez l\'App Secret Instagram', [], 'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        $builder->add('accessToken', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('API token (Manuel)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le token ou utilisez le bouton de connexion', [], 'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-8'],
        ]);

        $builder->add('nbrItems', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Nombre de posts', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var Instagram $instagram */
        $instagram = $form->getData();
        if ($instagram && $instagram->getAppId()) {
            $view->vars['auth_url'] = $this->instagramService->getAuthUrl($instagram->getAppId());
        }
    }

    public function getBlockPrefix(): string
    {
        return 'instagram_api';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Instagram::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
