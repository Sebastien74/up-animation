<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core as CoreEntities;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ColorFixtures.
 *
 * Color Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ColorFixtures::class, 'key' => 'color_fixtures'],
])]
class ColorFixtures
{
    private const array COLORS = [
        'primary' => '#540b01',
        'secondary' => '#68312c',
        'success' => '#5d7808',
        'info' => '#0081ac',
        'warning' => '#f0ad4e',
        'danger' => '#a82835',
        'danger-light:' => '#ee9da5',
        'light' => '#ee9da5',
        'dark' => '#212529',
        'link' => '#540b01',
        'white' => '#ffffff',
    ];
    private const array ACTIVE_COLORS = [
        'primary',
        'secondary',
        'white',
        'alert-success',
        'alert-danger',
        'alert-warning',
        'alert-info',
        'mask-icon',
        'msapplication-TileColor',
        'theme-color',
        'webmanifest-theme',
        'webmanifest-background',
        'browserconfig',
        'maintenance',
    ];
    private const array FAVICON = [
        'mask-icon' => '#8c3744',
        'msapplication-TileColor' => '#8c3744',
        'theme-color' => '#ffffff',
        'webmanifest-theme' => '#ffffff',
        'webmanifest-background' => '#ffffff',
        'browserconfig' => '#8c3744',
    ];
    private const array CATEGORIES = [
        'button', 'button-outline', 'color', 'background', 'alert', 'favicon',
    ];

    private int $position = 1;
    private array $yamlConfiguration = [];
    private ?User $user;

    /**
     * ColorFixtures constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Add Colors.
     */
    public function add(CoreEntities\Configuration $configuration, array $yamlConfiguration, ?User $user = null, ?CoreEntities\Website $websiteToDuplicate = null): void
    {
        $this->yamlConfiguration = $yamlConfiguration;
        $this->user = $user;

        if ($websiteToDuplicate instanceof CoreEntities\Website) {
            $this->generateDbColors($configuration, $websiteToDuplicate);
        } else {
            foreach (self::CATEGORIES as $category) {
                $this->generateColors($configuration, $category);
            }
        }
    }

    /**
     * Generate DB colors.
     */
    private function generateDbColors(CoreEntities\Configuration $configuration, CoreEntities\Website $websiteToDuplicate): void
    {
        foreach ($websiteToDuplicate->getConfiguration()->getColors() as $referColor) {
            $color = new CoreEntities\Color();
            $color->setAdminName($referColor->getAdminName());
            $color->setSlug($referColor->getSlug());
            $color->setCategory($referColor->getCategory());
            $color->setDeletable($referColor->isDeletable());
            $color->setActive($referColor->isActive());
            $color->setColor($referColor->getColor());
            $color->setPosition($color->getPosition());
            $color->setConfiguration($configuration);
            $color->setCreatedBy($this->user);
            $this->entityManager->persist($color);
        }
    }

    /**
     * Generate colors.
     */
    private function generateColors(CoreEntities\Configuration $configuration, string $category): void
    {
        $selfFavicons = !empty($this->yamlConfiguration['favicons']) ? $this->yamlConfiguration['favicons'] : self::FAVICON;
        $selfColors = !empty($this->yamlConfiguration['colors']) ? $this->yamlConfiguration['colors'] : self::COLORS;
        $colors = 'favicon' === $category ? $selfFavicons : $selfColors;

        foreach ($colors as $code => $hexadecimal) {
            if ('link' !== $code && 'button' !== $category || 'button' === $category) {
                $this->generateColor($configuration, $code, $hexadecimal, $category);
            }
        }

        if ('background' === $category) {
            $color = !empty($this->yamlConfiguration['colors']['primary']) ? $this->yamlConfiguration['colors']['primary'] : '#410c2c';
            $this->generateColor($configuration, 'maintenance', $color, 'background');
        }
    }

    /**
     * Generate color.
     */
    private function generateColor(CoreEntities\Configuration $configuration, string $code, string $hexadecimal, string $category): void
    {
        $categoryConfiguration = (object) $this->getCategoryConfiguration($category, $code);
        $slug = $categoryConfiguration->prefix.'-'.$code;
        $activeColors = !empty($this->yamlConfiguration['active_colors']) ? $this->yamlConfiguration['active_colors'] : self::ACTIVE_COLORS;
        $active = in_array($code, $activeColors) || in_array($slug, $activeColors);

        $color = new CoreEntities\Color();
        $color->setAdminName($categoryConfiguration->adminName);
        $color->setSlug(ltrim($slug, '-'));
        $color->setCategory($categoryConfiguration->category);
        $color->setDeletable(false);
        $color->setActive($active);
        $color->setColor($hexadecimal);
        $color->setPosition($this->position);
        $color->setConfiguration($configuration);
        $color->setCreatedBy($this->user);

        $this->entityManager->persist($color);
        ++$this->position;
    }

    /**
     * Get category configuration.
     */
    private function getCategoryConfiguration(string $category, string $code): array
    {
        if ('link' === $code) {
            return [
                'adminName' => $this->getTranslation($code),
                'prefix' => null,
                'category' => 'button',
            ];
        }

        $adminNames['button'] = [
            'adminName' => $this->translator->trans('Bouton', [], 'admin'),
            'prefix' => 'btn',
            'category' => 'button',
        ];

        $adminNames['button-outline'] = [
            'adminName' => $this->translator->trans('Bouton avec contour', [], 'admin'),
            'prefix' => 'btn-outline',
            'category' => 'button',
        ];

        $adminNames['color'] = [
            'adminName' => $this->translator->trans('Couleur principale', [], 'admin'),
            'category' => 'color',
        ];

        $adminNames['background'] = [
            'adminName' => $this->translator->trans('Couleur de fond', [], 'admin'),
            'prefix' => 'bg',
            'category' => 'background',
        ];

        $adminNames['alert'] = [
            'adminName' => $this->translator->trans('Couleur des alertes', [], 'admin'),
            'prefix' => 'alert',
            'category' => 'alert',
        ];

        $adminNames['favicon'] = [
            'adminName' => $this->translator->trans('Couleur de fond', [], 'admin'),
            'category' => 'favicon',
        ];

        return [
            'adminName' => isset($adminNames[$category]['adminName'])
                ? $adminNames[$category]['adminName'].' '.$this->getTranslation($code)
                : $this->getTranslation($code),
            'prefix' => $adminNames[$category]['prefix'] ?? null,
            'category' => $adminNames[$category]['category'] ?? null,
        ];
    }

    /**
     * Get translation.
     */
    private function getTranslation(string $code): string
    {
        $translations = [
            'primary' => $this->translator->trans('principal', [], 'admin'),
            'white' => $this->translator->trans('blanc', [], 'admin'),
            'secondary' => $this->translator->trans('secondaire', [], 'admin'),
            'success' => $this->translator->trans('de success', [], 'admin'),
            'danger' => $this->translator->trans("d'erreur", [], 'admin'),
            'warning' => $this->translator->trans("d'alerte", [], 'admin'),
            'info' => $this->translator->trans("d'information", [], 'admin'),
            'light' => $this->translator->trans('inactif', [], 'admin'),
            'dark' => $this->translator->trans('noir', [], 'admin'),
            'link' => $this->translator->trans('Lien classique', [], 'admin'),
        ];

        return $translations[$code] ?? $code;
    }
}
