<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Api\Facebook;
use App\Service\Content\FacebookService;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FacebookType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FacebookType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FacebookType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly FacebookService $facebookService
    ) {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('appId', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('App ID', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez l\'App ID Facebook', [], 'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        $builder->add('apiSecretKey', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('App Secret', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez l\'App Secret Facebook', [], 'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        $builder->add('pageId', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Page ID', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez l\'ID de la Page Facebook', [], 'admin')
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
        /** @var Facebook $facebook */
        $facebook = $form->getData();
        if ($facebook && $facebook->getAppId()) {
            $view->vars['auth_url'] = $this->facebookService->getAuthUrl($facebook->getAppId());
        }
    }

    public function getBlockPrefix(): string
    {
        return 'facebook_api';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facebook::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
