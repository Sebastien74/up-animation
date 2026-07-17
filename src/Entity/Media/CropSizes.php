<?php

declare(strict_types=1);

namespace App\Entity\Media;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * CropSizes.
 *
 * Objet embarqué (Doctrine Embeddable) : tailles de crop des vignettes par écran
 * (largeur + hauteur, en pixels), pour Ordinateur / Ordinateur portable /
 * Tablette / Mobile. Réutilisable par n'importe quelle entité via #[ORM\Embedded].
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Embeddable]
class CropSizes
{
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $cropWidthDesktop = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $cropHeightDesktop = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $cropWidthLaptop = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $cropHeightLaptop = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $cropWidthTablet = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $cropHeightTablet = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $cropWidthMobile = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $cropHeightMobile = null;

    public function getCropWidthDesktop(): ?int
    {
        return $this->cropWidthDesktop;
    }

    public function setCropWidthDesktop(?int $cropWidthDesktop): static
    {
        $this->cropWidthDesktop = $cropWidthDesktop;

        return $this;
    }

    public function getCropHeightDesktop(): ?int
    {
        return $this->cropHeightDesktop;
    }

    public function setCropHeightDesktop(?int $cropHeightDesktop): static
    {
        $this->cropHeightDesktop = $cropHeightDesktop;

        return $this;
    }

    public function getCropWidthLaptop(): ?int
    {
        return $this->cropWidthLaptop;
    }

    public function setCropWidthLaptop(?int $cropWidthLaptop): static
    {
        $this->cropWidthLaptop = $cropWidthLaptop;

        return $this;
    }

    public function getCropHeightLaptop(): ?int
    {
        return $this->cropHeightLaptop;
    }

    public function setCropHeightLaptop(?int $cropHeightLaptop): static
    {
        $this->cropHeightLaptop = $cropHeightLaptop;

        return $this;
    }

    public function getCropWidthTablet(): ?int
    {
        return $this->cropWidthTablet;
    }

    public function setCropWidthTablet(?int $cropWidthTablet): static
    {
        $this->cropWidthTablet = $cropWidthTablet;

        return $this;
    }

    public function getCropHeightTablet(): ?int
    {
        return $this->cropHeightTablet;
    }

    public function setCropHeightTablet(?int $cropHeightTablet): static
    {
        $this->cropHeightTablet = $cropHeightTablet;

        return $this;
    }

    public function getCropWidthMobile(): ?int
    {
        return $this->cropWidthMobile;
    }

    public function setCropWidthMobile(?int $cropWidthMobile): static
    {
        $this->cropWidthMobile = $cropWidthMobile;

        return $this;
    }

    public function getCropHeightMobile(): ?int
    {
        return $this->cropHeightMobile;
    }

    public function setCropHeightMobile(?int $cropHeightMobile): static
    {
        $this->cropHeightMobile = $cropHeightMobile;

        return $this;
    }

    /**
     * Vrai si au moins une taille (largeur ou hauteur) est renseignée.
     */
    public function isDefined(): bool
    {
        return null !== $this->cropWidthDesktop || null !== $this->cropHeightDesktop
            || null !== $this->cropWidthLaptop || null !== $this->cropHeightLaptop
            || null !== $this->cropWidthTablet || null !== $this->cropHeightTablet
            || null !== $this->cropWidthMobile || null !== $this->cropHeightMobile;
    }

    /**
     * Format attendu par le helper de rendu des vignettes (option "screensSizes").
     *
     * Chaque écran non renseigné hérite de la valeur la plus adaptée (l'écran
     * défini le plus proche), largeur et hauteur résolues indépendamment :
     * - un écran vide reprend d'abord la valeur de l'écran plus grand renseigné
     *   (desktop -> laptop -> tablet -> mobile) ;
     * - les écrans plus grands non renseignés retombent sur le plus petit défini.
     * Ex. seul desktop saisi -> tous les écrans en desktop ; tablet saisi sans
     * mobile -> mobile reprend tablet.
     *
     * @return array<string, array{width: ?int, height: ?int}>
     */
    public function toScreensSizes(): array
    {
        $order = ['desktop', 'laptop', 'tablet', 'mobile'];
        $raw = [
            'desktop' => ['width' => $this->cropWidthDesktop, 'height' => $this->cropHeightDesktop],
            'laptop' => ['width' => $this->cropWidthLaptop, 'height' => $this->cropHeightLaptop],
            'tablet' => ['width' => $this->cropWidthTablet, 'height' => $this->cropHeightTablet],
            'mobile' => ['width' => $this->cropWidthMobile, 'height' => $this->cropHeightMobile],
        ];

        $result = [];
        foreach (['width', 'height'] as $dim) {
            // Forward-fill : grand -> petit (desktop propage vers les plus petits).
            $last = null;
            foreach ($order as $screen) {
                if (null !== $raw[$screen][$dim]) {
                    $last = $raw[$screen][$dim];
                }
                $result[$screen][$dim] = $raw[$screen][$dim] ?? $last;
            }
            // Backward-fill : petit -> grand (comble les grands écrans restés vides).
            $last = null;
            foreach (array_reverse($order) as $screen) {
                if (null !== $result[$screen][$dim]) {
                    $last = $result[$screen][$dim];
                } elseif (null !== $last) {
                    $result[$screen][$dim] = $last;
                }
            }
        }

        return $result;
    }
}
