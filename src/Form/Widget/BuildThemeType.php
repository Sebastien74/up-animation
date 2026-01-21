<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * BuildThemeType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BuildThemeType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * BuildThemeType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Maintenance thème', [], 'admin'),
            'placeholder' => $this->translator->trans('Séléctionnez', [], 'admin'),
            'required' => false,
            'choices' => $this->getTemplates(),
            'display' => 'search',
            'translation_domain' => 'admin',
        ]);
    }

    /**
     * Get admin templates.
     */
    private function getTemplates(): array
    {
        return [
            $this->translator->trans('Basic', [], 'admin') => 'basic',
            $this->translator->trans('Diagonales', [], 'admin') => 'diagonals',
            $this->translator->trans('Carrés', [], 'admin') => 'squares',
            $this->translator->trans('Cercles', [], 'admin') => 'circles',
            $this->translator->trans('Blanc img', [], 'admin') => 'white',
            $this->translator->trans('Beige img', [], 'admin') => 'beige',
            $this->translator->trans('Félix', [], 'admin') => 'felix',
            $this->translator->trans('Personnalisé', [], 'admin') => 'custom',
        ];
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
