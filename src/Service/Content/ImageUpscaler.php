<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Media\CropSizes;
use App\Entity\Media\Media;
use App\Service\Interface\CoreLocatorInterface;
use Imagine\Gd\Imagine;
use Symfony\Component\Filesystem\Filesystem;

/**
 * ImageUpscaler.
 *
 * Quand des CropSizes imposent une taille de vignette (× retina) supérieure à
 * la résolution d'une image source, agrandit physiquement le fichier source
 * pour que la génération de vignettes produise la taille demandée (le moteur
 * de vignettes ne dépasse jamais la taille de la source).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImageUpscaler
{
    private const array SUPPORTED = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Garantit que chaque média source est au moins aussi grand que la plus
     * grande vignette attendue (crop max × retina).
     *
     * @param iterable<object> $mediaRelations relations média exposant getMedia()
     */
    public function ensureCropSizes(iterable $mediaRelations, CropSizes $cropSizes): void
    {
        if (!$cropSizes->isDefined()) {
            return;
        }

        // Taille source requise = la plus grande vignette attendue (tailles de
        // crop configurées, tous écrans confondus). Pas de facteur retina imposé.
        $requiredWidth = 0;
        $requiredHeight = 0;
        foreach ($cropSizes->toScreensSizes() as $screen) {
            $requiredWidth = max($requiredWidth, (int) ($screen['width'] ?? 0));
            $requiredHeight = max($requiredHeight, (int) ($screen['height'] ?? 0));
        }
        if ($requiredWidth <= 0 && $requiredHeight <= 0) {
            return;
        }

        foreach ($mediaRelations as $relation) {
            $media = is_object($relation) && method_exists($relation, 'getMedia') ? $relation->getMedia() : null;
            if ($media instanceof Media) {
                $this->ensureMinSize($media, $requiredWidth, $requiredHeight);
            }
        }
    }

    /**
     * Agrandit le fichier source du média (en conservant le ratio) si l'une de
     * ses dimensions est inférieure à la taille requise. Idempotent.
     */
    private function ensureMinSize(Media $media, int $requiredWidth, int $requiredHeight): void
    {
        $website = $media->getWebsite();
        $originalName = $media->getOriginalName();
        if (!$website || !$originalName) {
            return;
        }
        if (!in_array(strtolower((string) $media->getExtension()), self::SUPPORTED, true)) {
            return;
        }

        $path = $this->coreLocator->projectDir().'/public/uploads/'.$website->getUploadDirname().'/'.$originalName;
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $filesystem = new Filesystem();
        if (!$filesystem->exists($path)) {
            return;
        }

        $dimensions = @getimagesize($path);
        if (!$dimensions) {
            return;
        }
        [$width, $height] = $dimensions;
        if ($width <= 0 || $height <= 0) {
            return;
        }

        // Facteur d'échelle unique appliqué aux deux dimensions : le ratio
        // d'origine est donc TOUJOURS conservé. On prend le plus grand des deux
        // rapports pour que largeur ET hauteur atteignent la taille requise.
        $factor = max(
            $requiredWidth > 0 ? $requiredWidth / $width : 0,
            $requiredHeight > 0 ? $requiredHeight / $height : 0
        );
        if ($factor <= 1.0) {
            return; // déjà assez grand
        }

        try {
            $image = (new Imagine())->open($path);
            // scale() agrandit largeur et hauteur du même facteur -> ratio préservé.
            $image->resize($image->getSize()->scale($factor))->save($path);
        } catch (\Throwable) {
            return;
        }

        $this->clearThumbnails($originalName);
    }

    /**
     * Supprime les vignettes en cache d'un média (régénérées à la taille source
     * agrandie au prochain rendu).
     */
    private function clearThumbnails(string $originalName): void
    {
        $dir = $this->coreLocator->projectDir().'/public/thumbnails';
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && str_contains($file->getFilename(), $originalName)) {
                @unlink($file->getPathname());
            }
        }
    }
}
