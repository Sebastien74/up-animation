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
    private array $colorsCache = [];

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
        $request = $this->coreLocator->request();
        $website = !$website ? $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($request?->getHost()) : $website;
        $configurationId = $website->configuration->id;
        // Per-request memo (was cached in the session, which opened a session on every
        // front page and blocked shared-cache caching). Colors are website-global data.
        $colors = $this->colorsCache[$configurationId] ?? null;
        if (null === $colors) {
            $colors = $configurationId ? $this->coreLocator->em()->getRepository(Color::class)->findByConfiguration($configurationId) : [];
            $this->colorsCache[$configurationId] = $colors;
        }

        foreach ($colors as $color) {
            if ($color['category'] === $category && $color['slug'] === $slug) {
                if (str_contains($color['slug'], 'gradient') && str_contains($color['slug'], 'primary')) {
                    $color['color'] = 'linear-gradient(to bottom right, rgba(255, 62, 0, .8) , rgba(255, 190, 48, .8))';
                }
                return $color;
            }
        }

        if (!$refresh) {
            unset($this->colorsCache[$configurationId]);
            $this->color($category, $website, $slug, true);
        }

        return (object) ['color' => $slug];
    }
}
