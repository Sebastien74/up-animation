<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Media as MediaEntities;
use App\Entity\Security\User;
use App\Service\Core\Uploader;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * UploadedFileFixtures.
 *
 * Uploaded File Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => UploadedFileFixtures::class, 'key' => 'uploaded_file_fixtures'],
])]
class UploadedFileFixtures
{
    /**
     * UploadedFileFixtures constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly EntityManagerInterface $entityManager,
        private readonly Uploader $uploader,
    ) {
    }

    /**
     * Upload file.
     */
    public function uploadedFile(
        Website $website,
        string $path,
        string $locale,
        mixed $entity = null,
        ?string $category = null,
        ?string $relationCategory = null,
        ?User $user = null,
    ): ?MediaEntities\Media {
        $uploadedFile = $this->uploader->pathToUploadedFile($path);

        if ($uploadedFile) {
            $this->uploader->upload($uploadedFile, $website);

            $media = new MediaEntities\Media();
            $media->setWebsite($website);
            $media->setFilename($this->uploader->getFilename());
            $media->setName($this->uploader->getName());
            $media->setExtension($this->uploader->getExtension());
            $media->setCategory($category);
            $media->setCreatedBy($user);

            $intl = new MediaEntities\MediaIntl();
            $intl->setLocale($locale);
            $intl->setTitle($this->uploader->getName());
            $intl->setCreatedBy($user);
            $intl->setWebsite($website);
            $media->addIntl($intl);

            if ($entity) {
                $mediaRelationData = $this->coreLocator->metadata($entity, 'mediaRelations');
                $relation = new ($mediaRelationData->targetEntity)();
                $relation->setLocale($locale);
                $relation->setMedia($media);
                $relation->setCategorySlug($relationCategory);
                $entity->addMediaRelation($relation);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
            }

            if ($user && $entity) {
                $relation->setCreatedBy($user);
            }

            return $media;
        }

        return null;
    }

    /**
     * Generate Folder.
     */
    public function generateFolder(
        Website $website,
        string $adminName,
        string $slug,
        ?MediaEntities\Folder $parentFolder = null,
        ?User $user = null,
        bool $isWebmaster = true,
    ): MediaEntities\Folder {
        $folder = new MediaEntities\Folder();
        $folder->setAdminName($adminName);
        $folder->setWebsite($website);
        $folder->setWebmaster($isWebmaster);
        $folder->setSlug($slug);
        $folder->setDeletable(!$isWebmaster);
        $folder->setCreatedBy($user);

        $params = ['website' => $website, 'parent' => $parentFolder];
        $position = count($this->entityManager->getRepository(MediaEntities\Folder::class)->findBy($params)) + 1;

        $folder->setPosition($position);

        if ($parentFolder) {
            $folder->setLevel($parentFolder->getLevel() + 1);
            $folder->setParent($parentFolder);
            $this->entityManager->refresh($parentFolder);
        }

        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        return $folder;
    }
}
