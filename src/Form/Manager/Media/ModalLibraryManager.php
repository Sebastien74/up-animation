<?php

declare(strict_types=1);

namespace App\Form\Manager\Media;

use App\Entity\Core\Website;
use App\Entity\Media\Media;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ModalLibraryManager.
 *
 * Manage admin Media by modal Library form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ModalLibraryManager::class, 'key' => 'media_modal_library_form_manager'],
])]
class ModalLibraryManager
{
    private EntityManagerInterface $entityManager;
    private ?object $mediaRelationRepository = null;
    private ?object $repository = null;
    private object $metadata;

    /**
     * ModalLibraryManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->entityManager = $this->coreLocator->entityManager();
    }

    /**
     * Add MediaRelation.
     */
    public function add(Website $website, Media $media, ?string $options = null): void
    {
        $options = (object) json_decode($options);
        $this->repository = $this->entityManager->getRepository(urldecode($options->classname));
        $entity = $this->repository->find($options->entityId);
        $this->metadata = $this->coreLocator->metadata($entity, 'mediaRelations');
        $classname = method_exists($entity, 'getMedia') ? get_class($entity) : $this->metadata->targetEntity;
        $this->mediaRelationRepository = $this->entityManager->getRepository($classname);

        if ('single' === $options->type) {
            $this->addSingle($entity, $media, $options);
        } elseif ('multiple' === $options->type) {
            $this->addMultiple($website, $entity, $media);
        }

        $this->entityManager->flush();
    }

    /**
     * Single.
     */
    private function addSingle(mixed $entity, Media $media, $options = null): void
    {
        if ($entity && property_exists($options, 'mediaRelationId')) {

            $propertyToSet = property_exists($options, 'property') && $options->property ? $options->property : false;
            $identifier = $propertyToSet ?: (method_exists($entity, 'getMediaRelations') ? 'mediaRelations' : 'mediaRelation');
            $mediaRelation = $this->mediaRelationRepository->find($options->mediaRelationId);
            $mediaRelation->setMedia($media);

            if (!str_contains(get_class($entity), 'MediaRelation')) {
                $existing = $this->repository->createQueryBuilder('e')->select('e')
                    ->leftJoin('e.'.$identifier, 'mr')
                    ->andWhere('e.id = :id')
                    ->andWhere('mr.media = :media')
                    ->setParameter('media', $media)
                    ->setParameter('id', $entity->getId())
                    ->addSelect('mr')
                    ->getQuery()
                    ->getResult();
                if (!$existing) {
                    if ('mediaRelation' === $propertyToSet && method_exists($entity, 'getMediaRelation')) {
                        $setter = 'setMediaRelation';
                    } elseif ('mediaRelations' === $propertyToSet && method_exists($entity, 'getMediaRelations')) {
                        $setter = 'addMediaRelation';
                    } else {
                        $setter = method_exists($entity, 'getMediaRelations') ? 'addMediaRelation' : 'setMediaRelation';
                    }
                    $entity->$setter($mediaRelation);
                    $this->entityManager->persist($entity);
                }
            } else {
                $this->entityManager->persist($mediaRelation);
            }
        }
        if (property_exists($options, 'mediaId') && $entity instanceof Media) {
            $videoFormats = ['webm', 'vtt', 'mp4'];
            if (!in_array($entity->getScreen(), $videoFormats)) {
                $entity->setExtension($media->getExtension());
            }

            $entity->setName($media->getName());
            $entity->setFilename($media->getFilename());
            $entity->setCopyright($media->getCopyright());
            $entity->setNotContractual($media->isNotContractual());
            $this->entityManager->persist($entity);
        }
    }

    /**
     * Multiple.
     */
    private function addMultiple(Website $website, mixed $entity, Media $media): void
    {
        $locale = $website->getConfiguration()->getLocale();
        $localeEntity = $this->repository->createQueryBuilder('e')->select('e')
            ->leftJoin('e.mediaRelations', 'mr')
            ->andWhere('e.id = :id')
            ->andWhere('mr.locale = :locale')
            ->setParameter('id', $entity->getId())
            ->setParameter('locale', $locale)
            ->addSelect('mr')
            ->getQuery()
            ->getOneOrNullResult();

        $position = $localeEntity ? count($localeEntity->getMediaRelations()) + 1 : 1;
        $mediaRelation = new ($this->metadata->targetEntity)();
        $mediaRelation->setPosition($position);
        $mediaRelation->setLocale($locale);
        $mediaRelation->setMedia($media);

        $entity->addMediaRelation($mediaRelation);
        $this->entityManager->persist($entity);
    }
}
