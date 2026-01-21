<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Newsletter;

use App\Entity\Module\Newsletter\Campaign;
use App\Entity\Module\Newsletter\Email;
use App\Form\Validator\UniqEmailCampaign;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FrontType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FrontType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Campaign $campaign */
        $campaign = $options['form_data'];

        $recaptcha = new WidgetType\RecaptchaType($this->coreLocator);
        $recaptcha->add($builder, $options['form_data']);

        $constraints = $campaign->isInternalRegistration() ? [
            new Assert\NotBlank(message: $this->translator->trans('Vous devez renseigner votre e-mail.', [], 'front_form')),
            new Assert\Email(),
            new UniqEmailCampaign(),
        ] : [
            new Assert\NotBlank(message: $this->translator->trans('Vous devez renseigner votre e-mail.', [], 'front_form')),
            new Assert\Email(),
        ];

        $builder->add('email', Type\EmailType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Votre e-mail', [], 'front_form'),
                'class' => 'text-center text-md-center text-lg-start newsletter-form-email',
                'autocomplete' => 'off',
            ],
            'constraints' => $constraints,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Email::class,
            'form_data' => null,
            'translation_domain' => 'front_form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'front_newsletter';
    }
}
