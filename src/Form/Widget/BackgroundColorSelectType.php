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
 * BackgroundColorSelectType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BackgroundColorSelectType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?Website $website;
    private array $colors = [];

    /**
     * BackgroundColorType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRepository $websiteRepository,
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
            'attr' => ['class' => 'select-icons'],
            'choice_attr' => function ($color, $key, $value) {
                return [
                    'data-class' => str_contains($color, 'outline') ? 'square-outline' : 'square',
                    'data-color' => $this->colors[$color],
                ];
            },
        ]);
    }

    /**
     * Get WebsiteModel background colors.
     */
    private function getColors(): array
    {
        $colors = $this->website->getConfiguration()->getColors();
        $choices = [];
        foreach ($colors as $color) {
            if ('background' === $color->getCategory() && $color->isActive()) {
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
