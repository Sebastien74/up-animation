<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Media\Media;
use App\Entity\Media\MediaIntl;
use App\Entity\Media\MediaRelation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ClearMediasService.
 *
 * To clear unused Media & MediaRelation
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ClearMediasService::class, 'key' => 'clear_medias_service'],
])]
class ClearMediasService
{
    /**
     * ClearMediasService constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * To execute service.
     */
    public function execute(string $currentClassname): void
    {
        $referEntity = new $currentClassname();

        $unusedMediaRelations = $this->entityManager->getRepository(MediaRelation::class)->findUnused();
        if ($unusedMediaRelations) {
            if (1 === count($unusedMediaRelations) && method_exists($referEntity, 'getMediaRelations')) {
                $this->remove($this->getEntities($currentClassname));
            } else {
                $metasData = $this->entityManager->getMetadataFactory()->getAllMetadata();
                foreach ($metasData as $metadata) {
                    $classname = $metadata->getName();
                    $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
                    if ($baseEntity && method_exists($baseEntity, 'getMediaRelations')) {
                        $entities = $this->getEntities($classname);
                        if ($entities) {
                            $this->remove($entities);
                        }
                    }
                }
            }
        }

        $unusedMedias = $this->entityManager->getRepository(Media::class)->findUnused();
        if ($unusedMedias) {
            foreach ($unusedMedias as $unusedMedia) {
                try {
                    if ($unusedMedia->getId()) {
                        $this->entityManager->remove($unusedMedia);
                        $this->entityManager->flush();
                    }
                } catch (\Exception $exception) {
                    continue;
                }
            }
        }
    }

    /**
     * To entities with unused Media.
     */
    private function getEntities(string $classname): array
    {
        return $this->entityManager->getRepository($classname)
            ->createQueryBuilder('e')
            ->leftJoin('e.mediaRelations', 'mr')
            ->leftJoin('mr.media', 'm')
            ->andWhere('m.screen = :screen')
            ->andWhere('m.filename IS NULL')
            ->setParameter('screen', 'desktop')
            ->addSelect('mr')
            ->addSelect('m')
            ->getQuery()
            ->getResult();
    }

    /**
     * To remove entities.
     */
    private function remove(array $entities): void
    {
        foreach ($entities as $entity) {
            foreach ($entity->getMediaRelations() as $mediaRelation) {
                /* @var MediaRelation $mediaRelation */
                try {
                    $media = $mediaRelation->getMedia();
                    $asUnused = $media && !$media->getFilename() && !$this->checkIntl($media, $mediaRelation->getIntl());
                    if ($asUnused) {
                        $entity->removeMediaRelation($mediaRelation);
                        if ($mediaRelation->getId()) {
                            $this->entityManager->remove($mediaRelation);
                        }
                        if ($media instanceof Media && $media->getId()) {
                            $this->entityManager->remove($media);
                        }
                        $this->entityManager->flush();
                    }
                } catch (\Exception $exception) {
                    continue;
                }
            }
        }
    }

    /**
     * To check if intl have content.
     */
    private function checkIntl(?Media $media = null, mixed $intl = null): bool
    {
        $haveContent = false;
        if ($media instanceof Media && $intl) {
            $fieldsToCheck = ['title', 'body', 'introduction'];
            $metadata = $this->entityManager->getClassMetadata(MediaIntl::class);
            foreach ($metadata->getFieldNames() as $name) {
                if (in_array($name, $fieldsToCheck)) {
                    $getMethod = 'get'.ucfirst($name);
                    $value = method_exists($intl, $getMethod) ? $intl->$getMethod() : null;
                    if ($value) {
                        $haveContent = true;
                    }
                }
            }
        }
        $media->setDeletable(!$haveContent);
        $this->entityManager->persist($media);
        $this->entityManager->flush();

        return $haveContent;
    }
}
