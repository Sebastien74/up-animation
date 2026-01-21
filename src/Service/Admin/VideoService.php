<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Core\Website;
use App\Entity\Media\Media;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;

/**
 * VideoService.
 *
 * Manage videos
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => VideoService::class, 'key' => 'video_service'],
])]
class VideoService
{
    private ?Request $request;

    /**
     * VideoService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * To add videos.
     *
     * @throws NonUniqueResultException
     */
    public function add(Website $website, string $classname): void
    {
        $configuration = $website->getConfiguration();
        $defaultLocale = $configuration->getLocale();
        $interface = $this->coreLocator->interfaceHelper()->generate($classname);
        $entity = $this->coreLocator->em()->getRepository($interface['classname'])->find(intval($this->coreLocator->request()->get($interface['name'])));

        if ($entity) {
            $position = $this->getPosition($entity, $defaultLocale);
            $formats = ['mp4', 'webm', 'vtt'];
            foreach ($website->getConfiguration()->getAllLocales() as $locale) {
                $media = new Media();
                $media->setScreen('poster');
                $media->setWebsite($website);
                foreach ($formats as $format) {
                    $existing = $this->screenExist($media, $format);
                    if (!$existing) {
                        $this->addMediaScreen($website, $media, $format);
                    }
                }
                $this->addMediaRelation($entity, $media, $position, $locale);
            }
            $this->coreLocator->em()->flush();
        }
    }

    /**
     * Get position.
     */
    private function getPosition(mixed $entity, string $locale): int
    {
        $position = 1;
        foreach ($entity->getMediaRelations() as $mediaRelation) {
            if ($mediaRelation->getLocale() === $locale) {
                ++$position;
            }
        }

        return $position;
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
    private function addMediaScreen(Website $website, Media $media, string $format): void
    {
        $mediaFormat = new Media();
        $mediaFormat->setWebsite($website);
        $mediaFormat->setScreen($format);
        $media->addMediaScreen($mediaFormat);
    }

    /**
     * Add MediaRelation.
     */
    private function addMediaRelation(mixed $entity, Media $media, int $position, string $locale): void
    {
        $mediaClassname = $this->coreLocator->metadata($entity, 'mediaRelations')->targetEntity;
        $mediaRelation = new $mediaClassname();
        $mediaRelation->setLocale($locale);
        $mediaRelation->setMedia($media);
        $mediaRelation->setPosition($position);
        $entity->addMediaRelation($mediaRelation);
        $this->coreLocator->em()->persist($entity);
    }
}
