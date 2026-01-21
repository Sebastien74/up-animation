<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Media\Media;
use App\Entity\Module\Gallery\Category;
use App\Entity\Module\Gallery\Gallery;
use Exception;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * GalleryRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GalleryRuntime implements RuntimeExtensionInterface
{
    /**
     * To get all categories.
     */
    public function indexGalleryCategories(array $galleries = []): array
    {
        $categories = [];

        foreach ($galleries as $gallery) {
            /** @var Gallery $gallery */
            $category = $gallery->getCategory();
            if ($category instanceof Category) {
                $categories[$category->getPosition()] = $category;
            }
        }

        ksort($categories);

        return $categories;
    }

    /**
     * To get all medias for index gallery.
     *
     * @throws Exception
     */
    public function indexGalleryMedias(array $galleries = []): array
    {
        $medias = [];

        foreach ($galleries as $gallery) {
            /** @var Gallery $gallery */
            foreach ($gallery->getMediaRelations() as $mediaRelation) {
                $media = $mediaRelation->getMedia();
                if ($media instanceof Media && !empty($media->getFilename())) {
                    $date = $mediaRelation->getUpdatedAt() ?: ($mediaRelation->getCreatedAt() ?: new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                    $dateInString = $date->format('Ymdhis');
                    $medias[$dateInString] = [
                        'mediaRelation' => $mediaRelation,
                        'gallery' => $gallery,
                        'category' => $gallery->getCategory(),
                    ];
                }
            }
        }

        ksort($medias);

        return array_reverse($medias, true);
    }
}
