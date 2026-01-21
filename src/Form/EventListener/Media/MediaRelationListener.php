<?php

declare(strict_types=1);

namespace App\Form\EventListener\Media;

use App\Entity\Core\Entity;
use App\Entity\Media\Media;
use App\Form\EventListener\BaseListener;
use Symfony\Component\Form\FormEvent;

/**
 * MediaRelationListener.
 *
 * Listen MediaRelation Form attribute
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaRelationListener extends BaseListener
{
    /**
     * preSetData.
     */
    public function preSetData(FormEvent $event): void
    {
        $entity = $event->getData();
        if ($entity) {
            $defaultMedia = $this->getDefault($entity);
            $entityConfiguration = $this->entityManager->getRepository(Entity::class)->findOneBy([
                'className' => get_class($entity),
                'website' => $this->website->entity,
            ]);
            $mediaMulti = $entityConfiguration instanceof Entity ? $entityConfiguration->isMediaMulti() : false;
            if ($entity->getMediaRelations()->count() > 0 || !$mediaMulti) {
                foreach ($this->locales as $locale) {
                    $exist = $this->localeExist($entity, $locale);
                    if (!$exist) {
                        $this->addMedia($locale, $entity, $defaultMedia);
                    }
                }
            }
        }
    }

    /**
     * Get default locale Media.
     */
    private function getDefault(mixed $entity): ?Media
    {
        if ($entity) {
            foreach ($entity->getMediaRelations() as $relation) {
                if ($relation->getLocale() === $this->defaultLocale) {
                    return $relation->getMedia();
                }
            }
        }
        return null;
    }

    /**
     * Check if MediaRelation locale existing.
     */
    private function localeExist(mixed $entity, string $locale): bool
    {
        if ($entity) {
            foreach ($entity->getMediaRelations() as $relation) {
                if ($relation->getLocale() === $locale) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Add Media.
     */
    private function addMedia(string $locale, mixed $entity, ?Media $defaultMedia = null): void
    {
        if ($entity) {
            $media = !$defaultMedia ? new Media() : $defaultMedia;
            if (method_exists($media, 'getWebsite') && !$media->getWebsite()) {
                $media->setWebsite($this->website->entity);
            }
            $intlData = $this->coreLocator->metadata($entity, 'mediaRelations');
            $mediaRelation = new ($intlData->targetEntity)();
            $mediaRelation->setLocale($locale);
            $mediaRelation->setMedia($media);
            $entity->addMediaRelation($mediaRelation);
        }
    }
}
