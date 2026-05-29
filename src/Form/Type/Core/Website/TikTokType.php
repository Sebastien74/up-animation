<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Api\TikTok;
use App\Service\Content\Feed\TikTokService;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TikTokType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TikTokType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * TikTokType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TikTokService $tiktokService
    ) {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('appId', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Client Key / App ID', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le Client Key TikTok', [], 'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        $builder->add('appSecret', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Client Secret / App Secret', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le Client Secret TikTok', [], 'admin')
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
            'label' => $this->translator->trans('Nombre de vidéos', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var TikTok $tiktok */
        $tiktok = $form->getData();
        if ($tiktok && $tiktok->getAppId()) {
            $view->vars['auth_url'] = $this->tiktokService->getAuthUrl($tiktok->getAppId());
        }
    }

    public function getBlockPrefix(): string
    {
        return 'tiktok_api';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TikTok::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
