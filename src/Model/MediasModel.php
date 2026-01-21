<?php

declare(strict_types=1);

namespace App\Model;

use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;

/**
 * MediasModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class MediasModel extends BaseModel
{
    private static array $cache = [];

    /**
     * MediaModel constructor.
     */
    public function __construct(
        public readonly ?object $main = null,
        public readonly ?object $header = null,
        public readonly ?array $list = null,
        public readonly ?array $withoutMain = null,
        public readonly ?object $mainVideo = null,
        public readonly ?array $videos = null,
        public readonly ?object $mainFile = null,
        public readonly ?array $files = null,
        public readonly ?array $mediasAndVideos = null,
        public readonly bool $videoAsFirst = false,
        public readonly bool $haveMain = false,
        public readonly bool $haveMedias = false,
        public readonly bool $haveVideos = false,
        public readonly bool $haveFiles = false,
    ) {
    }

    /**
     * fromEntity.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(mixed $entity, CoreLocatorInterface $coreLocator, ?string $locale = null, ?bool $query = true, array $options = []): self
    {
        if (!$entity) {
            return new self();
        }

        self::setLocator($coreLocator);

        $entity = property_exists($entity, 'entity') && !method_exists($entity, 'getEntity') ? $entity->entity : $entity;
        $locale = $locale ?: self::$coreLocator->locale();

        if ($entity && $entity->getId() && isset(self::$cache['response'][get_class($entity)][$entity->getId()][$locale])) {
            return self::$cache['response'][get_class($entity)][$entity->getId()][$locale];
        }

        $metadata = self::$cache['metadata'][get_class($entity)] = self::$cache['metadata'][get_class($entity)] ?? self::$coreLocator->metadata($entity, 'mediaRelations');
        $mediaRelationsDb = $options['medias'] ?? ($metadata->targetEntity && $entity->getId() && $query ? self::mediaRelationsQuery($entity, $metadata, $locale) : self::mediaRelations($entity, $locale));

        $mainSet = $videoAsFirst = false;
        $mainPosition = $mainVideo = null;
        $main = $header = $medias = $mediasAndVideos = $mediaRelations = $file = $files = $videos = $mediasWithoutMain = [];

        foreach ($mediaRelationsDb as $key => $mediaRelation) {
            $mediaModel = MediaModel::fromEntity($mediaRelation, $coreLocator);
            if ('file' === $mediaModel->type) {
                $files[$key + 1] = $mediaModel;
                if (empty($file)) {
                    $file = $mediaModel;
                }
            } else {
                $mediaRelations[$key + 1] = $mediaModel;
                if ($mediaRelations[$key + 1]->path && ($mediaRelation->isMain() || (!$mainSet && !$mediaRelation->isHeader()))) {
                    $main = $mediaRelations[$key + 1];
                    $mainSet = true;
                    $mainPosition = $mediaRelations[$key + 1]->position;
                }
                if ($mediaRelation->isHeader() && $mediaRelations[$key + 1]->path) {
                    $header = $mediaRelations[$key + 1];
                }
                if ('video' === $mediaRelations[$key + 1]->type) {
                    $videos[] = $mediaRelations[$key + 1];
                    if (!$mainVideo) {
                        $mainVideo = $mediaRelations[$key + 1];
                        if (0 === $key) {
                            $videoAsFirst = true;
                        }
                    }
                } elseif ($mediaRelations[$key + 1]->path && !$mediaRelation->isHeader()) {
                    $medias[] = $mediaRelations[$key + 1];
                }
                $mediasAndVideos[] = $mediaRelations[$key + 1];
            }
        }
        ksort($medias);

        if (!self::$coreLocator->inAdmin() && method_exists($entity, 'getLayout')) {
            foreach ($mediasAndVideos as $key => $mediaRelation) {
                if ($main && $mediaRelation->mediaRelation->isHeader() || $main && $mediaRelation->id === $main->id) {
                    unset($mediasAndVideos[$key]);
                }
            }
        }

        foreach ($medias as $position => $mediaRelation) {
            if ($mediaRelation->intl && !$mediaRelation->intl->linkOnline) {
                unset($medias[$position]);
            }
            if ($mediaRelation->position !== $mainPosition) {
                $mediasWithoutMain[$position] = $mediaRelation;
            }
        }

        self::$cache['response'][get_class($entity)][$entity->getId()][$locale] = new self(
            main: $main ? (object) $main : null,
            header: $header ? (object) $header : null,
            list: $medias,
            withoutMain: $mediasWithoutMain,
            mainVideo: $mainVideo,
            videos: $videos,
            mainFile: $file ? (object) $file : null,
            files: $files,
            mediasAndVideos: $mediasAndVideos,
            videoAsFirst: $videoAsFirst,
            haveMain: !empty($main),
            haveMedias: !empty($medias),
            haveVideos: !empty($videos),
            haveFiles: !empty($files),
        );

        return self::$cache['response'][get_class($entity)][$entity->getId()][$locale];
    }

    /**
     * To get media relations by entities array and by locale.
     *
     * @throws QueryException|NonUniqueResultException|MappingException
     */
    public static function fromEntities(mixed $entity, CoreLocatorInterface $coreLocator, array $ids = []): self
    {
        if (!$entity) {
            return new self();
        }

        if (isset(self::$cache['medias'][get_class($entity)][$entity->getId()])) {
            return self::fromEntity($entity, $coreLocator, self::$coreLocator->locale(), false, ['medias' => self::$cache['medias'][get_class($entity)][$entity->getId()]]);
        }

        if (!isset(self::$cache['medias'][get_class($entity)])) {
            $metadata = self::$coreLocator->metadata($entity, 'mediaRelations');
            $mediaRelations = self::$coreLocator->em()->getRepository($metadata->targetEntity)
                ->createQueryBuilder('mr')
                ->innerJoin('mr.media', 'm')
                ->leftJoin('mr.intl', 'i')
                ->leftJoin('m.thumbs', 'mt')
                ->leftJoin('m.intls', 'mi')
                ->andWhere('m.filename IS NOT NULL')
                ->andWhere('m.screen = :screen')
                ->andWhere('mr.'.$metadata->mappedBy.' IN (:ids)')
                ->setParameter('screen', 'desktop')
                ->andWhere('mr.locale =  :locale')
                ->setParameter('ids', $ids)
                ->setParameter('locale', self::$coreLocator->locale())
                ->addSelect('i')
                ->addSelect('m')
                ->addSelect('mt')
                ->addSelect('mi')
                ->orderBy('mr.locale', 'ASC')
                ->addOrderBy('mr.position', 'ASC')
                ->getQuery()
                ->getResult();
            $getter = 'get'.ucfirst($metadata->mappedBy);
            self::$cache['medias'][get_class($entity)] = [];
            foreach ($mediaRelations as $mediaRelation) {
                if (self::$coreLocator->locale() === $mediaRelation->getLocale()) {
                    self::$cache['medias'][get_class($entity)][$mediaRelation->$getter()->getId()][] = $mediaRelation;
                }
            }
        }
        $medias = !empty(self::$cache['medias'][get_class($entity)][$entity->getId()]) ? self::$cache['medias'][get_class($entity)][$entity->getId()] : [];

        return self::fromEntity($entity, $coreLocator, self::$coreLocator->locale(), false, ['medias' => $medias]);
    }

    /**
     * To get media relations entity and locale.
     */
    private static function mediaRelationsQuery(mixed $entity, object $metadata, ?string $locale = null): array
    {
        $qb = $metadata->mappedBy ? self::$coreLocator->em()->getRepository($metadata->targetEntity)
            ->createQueryBuilder('mr')
            ->innerJoin('mr.media', 'm')
            ->leftJoin('mr.intl', 'i')
            ->leftJoin('m.thumbs', 'mt')
            ->leftJoin('m.intls', 'mi')
            ->andWhere('mr.'.$metadata->mappedBy.' = :entity')
            ->andWhere('mr.locale =  :locale')
            ->andWhere('m.screen IN (:screens)')
            ->setParameter('entity', $entity)
            ->setParameter('locale', $locale)
            ->setParameter('screens', ['desktop', 'poster'])
            ->addSelect('i')
            ->addSelect('m')
            ->addSelect('mt')
            ->addSelect('mi')
            ->orderBy('mr.locale', 'ASC')
            ->addOrderBy('mr.position', 'ASC') : [];

        if ($qb instanceof QueryBuilder && !self::$coreLocator->inAdmin()) {
            $qb->andWhere('m.filename IS NOT NULL');
            $qb->orWhere('m.filename IS NULL AND m.screen = :screen AND mr.locale = :locale')
                ->setParameter('screen', 'poster');
        }

        return $qb instanceof QueryBuilder ? $qb
            ->getQuery()
            ->getResult() : [];
    }

    /**
     * To get media relations entity and locale.
     */
    private static function mediaRelations(mixed $entity, ?string $locale = null): array
    {
        $medias = [];
        if (is_object($entity) && method_exists($entity, 'getMediaRelations')) {
            foreach ($entity->getMediaRelations() as $mediaRelation) {
                if ($locale === $mediaRelation->getLocale()) {
                    $medias[$mediaRelation->getPosition()] = $mediaRelation;
                }
            }
        }
        ksort($medias);

        return $medias;
    }
}
