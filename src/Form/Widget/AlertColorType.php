<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Website;
use App\Repository\Core\WebsiteRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AlertColorType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AlertColorType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?Website $website;
    private array $colors = [];

    /**
     * AlertColorType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRepository $websiteRepository
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->website = $this->websiteRepository->find($this->coreLocator->requestStack()->getMainRequest()->get('website'));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Couleur de fond', [], 'admin'),
            'expanded' => false,
            'required' => false,
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'choices' => $this->getColors(),
            'attr' => [
                'class' => 'select-icons',
            ],
            'choice_attr' => function ($color, $key, $value) {
                return [
                    'data-class' => 'square',
                    'data-color' => $this->colors[$color],
                ];
            },
        ]);
    }

    /**
     * Get WebsiteModel colors.
     */
    private function getColors(): array
    {
        $colors = $this->website->getConfiguration()->getColors();
        $choices = [];
        foreach ($colors as $color) {
            if ('alert' === $color->getCategory() && $color->isActive()) {
                $choices[$color->getAdminName()] = $color->getSlug();
                $this->colors[$color->getSlug()] = $color->getColor();
            }
        }

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
