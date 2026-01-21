<?php

declare(strict_types=1);

namespace App\Form\Type\Translation;

use App\Entity\Translation\TranslationDomain;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * DomainType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DomainType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * DomainType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder);

        $builder->add('extract', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans("Ajouter à l'extraction", [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('forTranslator', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Pour le traducteur', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TranslationDomain::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
