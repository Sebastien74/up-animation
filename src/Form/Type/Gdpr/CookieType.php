<?php

declare(strict_types=1);

namespace App\Form\Type\Gdpr;

use App\Entity\Gdpr\Cookie;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CookieType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CookieType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CookieType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, [
            'label' => $this->translator->trans('Code du Cookie', [], 'admin'),
            'required' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le code', [], 'admin'),
            ],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cookie::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
