<?php

declare(strict_types=1);

namespace App\Form\Type\Seo\Configuration;

use App\Entity\Api\FacebookIntl;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FacebookIntlType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FacebookIntlType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FacebookIntlType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('domainVerification', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('DomainModel verification', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez la clé', [], 'admin'),
                'group' => 'col-12',
            ],
        ]);

        $builder->add('pixel', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Pixel ID', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez la clé', [], 'admin'),
                'group' => 'col-12',
            ],
        ]);

        $builder->add('phoneTrack', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Activer Phone track', [], 'admin'),
            'attr' => ['group' => 'col-12 d-flex align-items-end', 'class' => 'w-100'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FacebookIntl::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
