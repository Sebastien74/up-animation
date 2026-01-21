<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Website;
use App\Repository\Core\WebsiteRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * BackgroundColorType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BackgroundColorType extends AbstractType
{
    private ?Website $website;

    /**
     * BackgroundColorType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRepository $websiteRepository,
    ) {
        $this->website = $this->websiteRepository->find($this->coreLocator->requestStack()->getMainRequest()->get('website'));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'expanded' => true,
            'choices' => $this->getColors(),
        ]);
    }

    /**
     * Get WebsiteModel background colors.
     */
    private function getColors(): array
    {
        $haveWhite = false;
        $colors = $this->website->getConfiguration()->getColors();
        $choices['transparent'] = null;

        foreach ($colors as $color) {
            if ('bg-white' === $color->getSlug()) {
                $haveWhite = true;
            }
            if ('background' === $color->getCategory() && $color->isActive()) {
                $choices[$color->getAdminName()] = $color->getSlug();
            }
        }

        if (!$haveWhite) {
            $choices['white'] = 'bg-white';
        }

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
