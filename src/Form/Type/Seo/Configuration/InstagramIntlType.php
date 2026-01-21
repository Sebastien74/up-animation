<?php

declare(strict_types=1);

namespace App\Form\Type\Seo\Configuration;

use App\Entity\Api\InstagramIntl;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * InstagramIntlType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InstagramIntlType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * InstagramIntlType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('widget', Type\TextareaType::class, [
            'label' => $this->translator->trans('Widget', [], 'admin'),
            'required' => false,
            'editor' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Insérez le script', [], 'admin'),
                'group' => 'col-12',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InstagramIntl::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
