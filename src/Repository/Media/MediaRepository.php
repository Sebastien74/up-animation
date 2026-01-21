<?php

declare(strict_types=1);

namespace App\Repository\Media;

use App\Entity\Core\Website;
use App\Entity\Media\Folder;
use App\Entity\Media\Media;
use App\Model\Core\WebsiteModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MediaRepository.
 *
 * @extends ServiceEntityRepository<Media>
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaRepository extends ServiceEntityRepository
{
    /**
     * MediaRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Media::class);
    }

    /**
     * Find too heavy files.
     *
     * @return array<Media>
     */
    public function findTooHeavyFiles(WebsiteModel $website, array $filenames = [], array $filesSizes = []): array
    {
        $medias = $this->createQueryBuilder('m')
            ->andWhere('m.screen = :screen')
            ->andWhere('m.website = :website')
            ->andWhere('m.filename IN (:filenames)')
            ->setParameter('website', $website->entity)
            ->setParameter('screen', 'desktop')
            ->setParameter('filenames', $filenames)
            ->getQuery()->getResult();

        $response = [];
        $extensions = ['jpg', 'jpeg', 'gif', 'png', 'webp'];
        foreach ($medias as $media) {
            if (!empty($filesSizes[$media->getFilename()]) && $media->getExtension() && in_array($media->getExtension(), $extensions)) {
                $mediaSize = $this->sizeKey($filesSizes[$media->getFilename()], $response);
                $response[$mediaSize] = $media;
                krsort($response);
            }
        }

        return $response;
    }

    /**
     * To get size key.
     */
    private function sizeKey(int $size, array $response): int
    {
        if (isset($response[$size])) {
            return $this->sizeKey($size + 1, $response);
        }

        return $size;
    }

    /**
     * Find by WebsiteModel and Folder.
     *
     * @return array<Media>
     */
    public function findByWebsiteAndFolder(WebsiteModel $website, ?Folder $folder = null): array
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->andWhere('m.screen IN (:screens)')
            ->andWhere('m.website = :website')
            ->andWhere('m.name IS NOT NULL')
            ->setParameter('website', $website->entity)
            ->setParameter('screens', ['desktop', 'mp4', 'webm', 'vtt']);
        if (!$folder) {
            $queryBuilder->andWhere('m.folder IS NULL');
        } else {
            $queryBuilder->andWhere('m.folder = :folder')
                ->setParameter('folder', $folder);
        }

        return $queryBuilder->orderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by WebsiteModel and Folder.
     *
     * @return array<Media>
     */
    public function findByWebsiteAndExtensions(Website $website, array $extensions = []): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.screen = :screen')
            ->andWhere('m.website = :website')
            ->andWhere('m.extension IN (:extensions)')
            ->setParameter('website', $website)
            ->setParameter('screen', 'desktop')
            ->setParameter('extensions', $extensions)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get unused Media[].
     *
     * @return array<Media>
     */
    public function findUnused(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.screen = :screen')
            ->andWhere('m.filename IS NULL')
            ->andWhere('m.deletable = :deletable')
            ->setParameter('screen', 'desktop')
            ->setParameter('deletable', true)
            ->getQuery()
            ->getResult();
    }
}
