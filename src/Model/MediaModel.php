<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Core\ConfigurationMediaRelation;
use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Media\Media;
use App\Entity\Module\Menu\LinkMediaRelation;
use App\Service\Core\FileInfo;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * MediaModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class MediaModel extends BaseModel
{
    private static array $IMG_EXTENSIONS = ['jpg', 'jpeg', 'png', 'svg', 'gif', 'tiff', 'webp', 'raw', 'heic'];
    private static array $cache = [];
    private static ?object $website = null;

    /**
     * MediaModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?object $mediaRelation = null,
        public readonly ?Media $media = null,
        public readonly ?Media $entity = null,
        public readonly ?object $targetPage = null,
        public readonly ?string $targetLink = null,
        public readonly ?string $filename = null,
        public readonly ?string $type = null,
        public readonly ?object $intl = null,
        public readonly ?object $mediaIntl = null,
        public readonly ?string $videoLink = null,
        public readonly ?string $path = null,
        public readonly ?string $locale = null,
        public readonly ?string $title = null,
        public readonly ?string $copyright = null,
        public readonly ?string $body = null,
        public readonly ?string $introduction = null,
        public readonly ?bool $haveContent = null,
        public readonly ?bool $haveMedia = null,
        public readonly ?bool $main = null,
        public readonly ?bool $header = null,
        public readonly ?bool $hideHover = null,
        public readonly ?bool $popup = null,
        public readonly ?bool $downloadable = null,
        public readonly ?bool $radius = null,
        public readonly ?int $maxWidth = null,
        public readonly ?int $maxHeight = null,
        public readonly ?int $tabletMaxWidth = null,
        public readonly ?int $tabletMaxHeight = null,
        public readonly ?int $mobileMaxWidth = null,
        public readonly ?int $mobileMaxHeight = null,
        public readonly ?\DateTime $cacheDate = null,
        public readonly ?string $titlePosition = null,
        public readonly ?string $titleAlignment = null,
        public readonly ?int $position = null,
        public readonly ?FileInfo $fileInfo = null,
        public readonly ?string $extension = null,
        public readonly ?string $pictogram = null,
        public readonly ?int $pictogramMaxWidth = null,
        public readonly ?int $pictogramMaxHeight = null,
    ) {
    }

    /**
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(mixed $entity, CoreLocatorInterface $coreLocator, bool $query = true): self
    {
        self::setLocator($coreLocator);

        $entity = $entity && property_exists($entity, 'entity') && !method_exists($entity, 'getEntity') ? $entity->entity : $entity;

        if ($entity && !method_exists($entity, 'getId')) {
            return new self();
        }

        if (!self::$coreLocator->inAdmin() && $entity && $entity->getId() && isset(self::$cache['response'][get_class($entity)][$entity->getId()][self::$coreLocator->locale()])) {
            return self::$cache['response'][get_class($entity)][$entity->getId()][self::$coreLocator->locale()];
        }

        $isFront = self::$coreLocator->request() && !str_contains(self::$coreLocator->request()->getUri(), '/admin-'.$_ENV['SECURITY_TOKEN'].'/');
        $metadata = $entity && isset(self::$cache['metadata'][get_class($entity)])
            ? self::$cache['metadata'][get_class($entity)] : ($entity ? self::$coreLocator->em()->getClassMetadata(get_class($entity)) : null);
        if ($entity && $metadata) {
            self::$cache['metadata'][get_class($entity)] = $metadata;
        }
        $metadata = $metadata ? $metadata->getAssociationMappings() : [];
        $asMedia = !empty($metadata['media']['targetEntity']) && Media::class === $metadata['media']['targetEntity'] && !$entity instanceof Media;
        $mediaRelation = $asMedia ? $entity : ($entity && $query && !$entity instanceof Media ? self::mediaRelation($entity)
            : ($entity && !$entity instanceof Media ? self::mediaRelationByCollection($entity) : null));
        $media = $mediaRelation ? $mediaRelation->getMedia() : ($entity instanceof Media ? $entity : null);
        $website = $isFront ? self::$coreLocator->em()->getRepository(Website::class)->findOneByHost(self::$coreLocator->request()->getHost(), false, true)
            : (!self::$website && $media ? $media->getWebsite() : self::$website);
        $websiteDirname = $website ? $website->getUploadDirname() : null;
        $intl = $mediaRelation && !$mediaRelation instanceof ConfigurationMediaRelation ? IntlModel::fromEntity($mediaRelation, $coreLocator) : null;
        $targetPage = $intl?->linkTargetPage;
        $titlePosition = $media ? $media->getTitlePosition() : null;
        $positionMatches = $titlePosition ? explode('-', $titlePosition) : [];
        $fileInfo = $media ? self::$coreLocator->fileInfo()->file($website, $media->getFilename()) : null;
        $asVideo = ($media && 'poster' === $media->getScreen()) || ($intl && $intl->video);
        $pictogram = $mediaRelation && $mediaRelation->getPictogram() ? $mediaRelation->getPictogram()
            : ($intl && $intl->pictogram ? $intl->pictogram : ($targetPage && $targetPage->getPictogram() ? $targetPage->getPictogram() : null));

        $response = new self(
            id: self::getContent('id', $mediaRelation),
            mediaRelation: $mediaRelation,
            media: $media,
            entity: $media,
            targetPage: $targetPage,
            targetLink: $targetPage ? $intl?->link : null,
            filename: $media ? $media->getFilename() : '',
            type: $asVideo ? 'video' : ($media && in_array($media->getExtension(), self::$IMG_EXTENSIONS) ? 'img' : 'file'),
            intl: $intl,
            mediaIntl: $media ? IntlModel::fromEntity($media, $coreLocator, false) : null,
            videoLink: $asVideo ? self::videoUrl($intl, $media) : null,
            path: self::path($websiteDirname, $media),
            locale: self::getContent('locale', $mediaRelation),
            title: $intl && $intl->title ? $intl->title : self::getContent('title', $mediaRelation),
            copyright: $intl && self::getContent('copyright', $intl) ? self::getContent('copyright', $intl) : self::getContent('copyright', $media),
            body: $intl && $intl->body ? $intl->body : self::getContent('body', $mediaRelation),
            introduction: $intl && $intl->introduction ? $intl->introduction : self::getContent('introduction', $mediaRelation),
            haveContent: $intl && ($intl->title || $intl->introduction || $intl->body),
            haveMedia: $media && $media->getFilename(),
            main: self::getContent('main', $mediaRelation, true),
            header: self::getContent('header', $mediaRelation, true),
            hideHover: self::getContent('hideHover', $media, true),
            popup: self::getContent('popup', $mediaRelation, true),
            downloadable: self::getContent('downloadable', $mediaRelation, true),
            radius: self::getContent('radius', $mediaRelation, true),
            maxWidth: self::getContent('maxWidth', $mediaRelation),
            maxHeight: self::getContent('maxHeight', $mediaRelation),
            tabletMaxWidth: self::getContent('tabletMaxWidth', $mediaRelation),
            tabletMaxHeight: self::getContent('tabletMaxHeight', $mediaRelation),
            mobileMaxWidth: self::getContent('mobileMaxWidth', $mediaRelation),
            mobileMaxHeight: self::getContent('mobileMaxHeight', $mediaRelation),
            cacheDate: self::getContent('cacheDate', $mediaRelation),
            titlePosition: !empty($positionMatches[0]) ? $positionMatches[0] : null,
            titleAlignment: !empty($positionMatches[1]) ? $positionMatches[1] : null,
            position: self::getContent('position', $mediaRelation),
            fileInfo: $fileInfo,
            extension: $media ? $media->getExtension() : null,
            pictogram: $pictogram,
            pictogramMaxWidth: self::getContent('pictogramMaxWidth', $mediaRelation),
            pictogramMaxHeight: self::getContent('pictogramMaxHeight', $mediaRelation),
        );

        if ($entity) {
            self::$cache['response'][get_class($entity)][$entity->getId()][self::$coreLocator->locale()] = $response;
        }

        return $response;
    }

    /**
     * To get path.
     */
    private static function path(?string $websiteDirname = null, ?Media $media = null): ?string
    {
        $path = $websiteDirname && $media && $media->getFilename() ? '/uploads/'.$websiteDirname.'/'.$media->getFilename() : null;

        if ($media && $media->getFilename() && str_contains($media->getFilename(), '/medias/')) {
            $path = $media->getFilename();
        }

        return $path;
    }

    /**
     * To get media relation by locale.
     *
     * @throws NonUniqueResultException
     */
    private static function mediaRelation(mixed $entity): ?object
    {
        $isMediaLoader = self::$coreLocator->request()->getUri() && str_contains(self::$coreLocator->request()->getUri(), '_fragment');
        $metadata = self::$coreLocator->metadata($entity, 'mediaRelations');

        $qb = self::$coreLocator->em()->getRepository($metadata->targetEntity)
            ->createQueryBuilder('mr')
            ->innerJoin('mr.media', 'm')
            ->andWhere('mr.'.$metadata->mappedBy.' = :entity')
            ->andWhere('mr.locale =  :locale')
            ->andWhere('m.filename IS NOT NULL')
            ->setParameter('entity', $entity)
            ->setParameter('locale', self::$coreLocator->locale())
            ->addSelect('m')
            ->getQuery();

        if ($isMediaLoader && self::$coreLocator->website()->configuration->mediasSecondary) {
            $mediaRelations = $qb->getResult();
            $mediaRelation = !empty($mediaRelations[0]) ? $mediaRelations[0] : null;
        } else {
            $mediaRelation = $qb->getOneOrNullResult();
        }

        return $mediaRelation;
    }

    /**
     * To get media relation by locale in collection.
     */
    public static function mediaRelationByCollection(mixed $entity): mixed
    {
        if (method_exists($entity, 'getMediaRelations') && !$entity->getMediaRelations()->isEmpty()) {
            foreach ($entity->getMediaRelations() as $mediaRelation) {
                if (self::$coreLocator->locale() === $mediaRelation->getLocale()) {
                    return $mediaRelation;
                }
            }
        }

        return null;
    }

    /**
     * To get video Url.
     */
    private static function videoUrl(?IntlModel $intlModel = null, ?Media $media = null): ?string
    {
        if ($intlModel && $intlModel->video) {
            return $intlModel->video;
        }

        if ($media) {
            foreach ($media->getMediaScreens() as $screenMedia) {
                if ($screenMedia->getFilename()) {
                    return self::$coreLocator->schemeAndHttpHost().'/uploads/'.self::$coreLocator->website()->uploadDirname.'/'.$screenMedia->getFilename();
                }
            }
        }

        return null;
    }
}
