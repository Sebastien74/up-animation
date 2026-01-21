<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Media\Folder;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * MapFixtures.
 *
 * Map Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => MapFixtures::class, 'key' => 'map_fixtures'],
])]
class MapFixtures
{
    private const array MARKERS_COLORS = [
        'blue', 'green', 'grey', 'orange', 'pink', 'yellow',
    ];

    /**
     * MapFixtures constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UploadedFileFixtures $uploader,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Add Map.
     */
    public function add(Folder $webmasterFolder, Website $website, ?User $user = null): void
    {
        $this->addMarkers($webmasterFolder, $website, $user);
    }

    /**
     * To add Markers.
     */
    private function addMarkers(Folder $webmasterFolder, Website $website, ?User $user = null): void
    {
        $mediaFolder = $this->uploader->generateFolder($website, 'Map', 'map', $webmasterFolder, $user);
        foreach (self::MARKERS_COLORS as $color) {
            $path = $this->projectDir.'/assets/medias/images/default/map/marker-'.$color.'.svg';
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            $media = $this->uploader->uploadedFile($website, $path, $website->getConfiguration()->getLocale(), null, 'map', null, $user);
            $media->setFolder($mediaFolder);
            $this->entityManager->persist($media);
            $this->entityManager->flush();
        }
    }
}
