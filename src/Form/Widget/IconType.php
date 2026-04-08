<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * IconType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IconType extends AbstractType
{
    private TranslatorInterface $translator;
    private array $icons;

    /**
     * IconType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $website = $this->coreLocator->website()?->entity;
        $this->icons = $website ? $website->getConfiguration()->getIcons()->toArray() : [];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Icône', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'choices' => $this->getIcons(),
            'dropdown_class' => 'icons-selector',
            'row_attr' => function (OptionsResolver $attr) {
                $attr->setDefaults([
                    'class' => 'col-12 col-md-12 col-lg-4 img-icons',
                ]);
            },
            'attr' => function (OptionsResolver $attr) {
                $attr->setDefaults([
                    'data-config' => false,
                    'class' => 'col-12 select-2 select-icons',
                ]);
            },
            'choice_attr' => function ($icon, $key, $value) {
                return ['data-image' => $icon];
            },
        ]);
    }

    /**
     * Get WebsiteModel icons.
     */
    private function getIcons(): array
    {
        $choices = [];
        $choices[$this->translator->trans('Séléctionnez', [], 'admin')] = '';
        foreach ($this->icons as $icon) {
            $choices[$icon->getPath()] = $icon->getPath();
        }

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
