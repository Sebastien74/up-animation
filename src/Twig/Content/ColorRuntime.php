<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Core\Color;
use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * ColorRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ColorRuntime implements RuntimeExtensionInterface
{
    /**
     * ColorRuntime constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get colors.
     */
    public function colors(WebsiteModel $website): array
    {
        $colorsBb = $this->coreLocator->em()->getRepository(Color::class)->findByConfiguration($website->configuration->id);
        $colors = [];

        foreach ($colorsBb as $color) {
            if ($color['active']) {
                $colors[$color['category']][] = $color['slug'];
            }
        }
        if (empty($colors['background']) || !in_array('bg-white', $colors['background'])) {
            $colors['background'][] = 'bg-white';
        }

        return $colors;
    }

    /**
     * Get color.
     */
    public function color(string $category, ?WebsiteModel $website = null, ?string $slug = null, bool $refresh = false): mixed
    {
        $website = !$website ? $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($this->coreLocator->request()->getHost())
            : $website;
        $configurationId = $website->configuration->id;
        $session = $this->coreLocator->request()->getSession();
        $colorsSession = $session->get('website_colors_'.$configurationId) ? $session->get('website_colors_'.$configurationId) : [];
        $colors = !$colorsSession && $configurationId ? $this->coreLocator->em()->getRepository(Color::class)->findByConfiguration($configurationId) : $colorsSession;
        $session->set('website_colors_'.$configurationId, $colors);

        foreach ($colors as $color) {
            if ($color['category'] === $category && $color['slug'] === $slug) {
                return $color;
            }
        }

        if (!$refresh) {
            $session->remove('website_colors_'.$configurationId);
            $this->color($category, $website, $slug, true);
        }

        return (object) ['color' => $slug];
    }
}
