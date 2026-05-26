<?php

declare(strict_types=1);

namespace App\Form\EventListener\Media;

use App\Entity\Media\Media;
use App\Form\EventListener\BaseListener;
use Symfony\Component\Form\FormEvent;

/**
 * VideoListener.
 *
 * Listen Video media
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class VideoListener extends BaseListener
{
    /**
     * preSetData.
     */
    public function preSetData(FormEvent $event): void
    {
        /** @var Media $entity */
        $entity = $event->getData();
        if ($entity) {
            $flush = false;
            $formats = ['mp4', 'webm', 'vtt'];
            $entity->setScreen('poster');
            foreach ($formats as $format) {
                $existing = $this->screenExist($entity, $format);
                if (!$existing) {
                    $this->addScreen($entity, $format);
                    if ('admin_block_edit' === $this->coreLocator->request()->attributes->get('_route')) {
                        $flush = true;
                    }
                }
            }
        }
    }

    /**
     * Check if media screen existing.
     */
    private function screenExist(Media $media, string $format): bool
    {
        foreach ($media->getMediaScreens() as $screen) {
            if ($screen->getScreen() === $format) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add screen Media.
     */
    private function addScreen(Media $media, string $format): void
    {
        $mediaFormat = new Media();
        $mediaFormat->setWebsite($this->website->entity);
        $mediaFormat->setScreen($format);
        $media->addMediaScreen($mediaFormat);
    }
}
