<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Layout\Page;
use App\Entity\Media\Media;
use App\Entity\Seo\Url;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * DeleteService.
 *
 * Manage entity deletion
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => DeleteService::class, 'key' => 'core_delete_service'],
])]
class DeleteService
{
    private ?Request $request;

    /**
     * DeleteService constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly AdminLocatorInterface $adminLocator,
        private readonly IndexHelper $indexHelper,
    ) {
    }

    /**
     * Remove an Entity.
     *
     * @throws NonUniqueResultException
     */
    public function execute(string $classname, mixed $entities): void
    {
        $this->coreLocator->request()->getSession()->set('entityPostClassname', $classname);

        $interface = $this->coreLocator->interfaceHelper()->generate($classname);
        $repository = $this->coreLocator->em()->getRepository($classname);
        $entityToDelete = $repository->find($this->coreLocator->requestGet($interface['name']));

        if ($entityToDelete && !$this->coreLocator->isEntityWebsiteAllowed($entityToDelete)) {
            throw new AccessDeniedHttpException('Entity does not belong to the current website.');
        }

        if ($entityToDelete instanceof Media && $entityToDelete->getScreen()) {
            $this->resetMedia($entityToDelete);

            return;
        }

        if ($entityToDelete) {
            $this->adminLocator->indexHelper()->execute($classname, $interface, 'all');
            if (!$entities) {
                $pagination = $this->indexHelper->getPagination();
                if ($pagination instanceof SlidingPagination) {
                    $entities = $pagination->getItems();
                } elseif (is_array($pagination)) {
                    $entities = $pagination;
                } else {
                    $entities = [];
                }
            }
            $this->remove($entities, $entityToDelete, is_array($interface) ? $interface : []);
        }

        $masterField = is_array($interface) && !empty($interface['masterField']) ? $interface['masterField'] : null;
        $masterFieldGetter = $masterField ? 'get'.ucfirst($masterField) : null;
        $masterEntity = $masterFieldGetter && $entityToDelete && method_exists($entityToDelete, $masterFieldGetter) ? $entityToDelete->$masterFieldGetter() : null;

        if (is_object($masterEntity)) {
            $this->coreLocator->em()->refresh($masterEntity);
        }
    }

    /**
     * Reset screen Media.
     */
    private function resetMedia(Media $media): void
    {
        /** Remove file */
        $fileDirname = $this->coreLocator->projectDir().'/public/uploads/'.$media->getWebsite()->getUploadDirname().'/'.$media->getOriginalName();
        $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);
        $filesystem = new Filesystem();

        if (!$media->getMedia() && $filesystem->exists($fileDirname) && !is_dir($fileDirname) && $media->getOriginalName()) {
            $filesystem->remove($fileDirname);
        }

        /* Reset Media */
        $media->setName(null);
        $media->setOriginalName(null);
        $media->setExtension(null);

        $this->coreLocator->em()->persist($media);
        $this->coreLocator->em()->flush();
    }

    /**
     * Remove Entity & set others Entities positions.
     */
    private function remove(mixed $entities, mixed $entityToDelete, array $interface = []): void
    {
        if (is_object($entityToDelete) && method_exists($entityToDelete, 'getLevel')
            && method_exists($entityToDelete, 'getParent')) {
            $this->levels($entities, $entityToDelete);
        } else {
            $this->positions($entities, $entityToDelete);
        }

        if (is_object($entityToDelete) && method_exists($entityToDelete, 'getUrls')) {
            if ($entityToDelete instanceof Page && 'build.html.twig' === $entityToDelete->getTemplate()) {
                $this->coreLocator->em()->remove($entityToDelete);
            } else {
                foreach ($entityToDelete->getUrls() as $url) {
                    /* @var Url $url */
                    $url->setAsIndex(false);
                    $url->setOnline(false);
                    $url->setHideInSitemap(true);
                    $url->setArchived(true);
                    $this->coreLocator->em()->persist($url);
                }
            }
        } else {
            $this->detachFromMasterCollection($entityToDelete, $interface);
            $this->coreLocator->em()->remove($entityToDelete);
        }
        $this->coreLocator->em()->flush();
    }

    /**
     * Detach the entity from its master (parent) inverse collection before removal.
     *
     * When a child entity (Zone/Col/Block…) is removed via em()->remove() but is still
     * referenced in its parent's cascade:['persist'] collection, a parent touched during
     * the same flush (see LayoutSubscriber) cascades the persist back onto the child and
     * cancels the scheduled deletion. Removing it from the parent collection first triggers
     * orphanRemoval and prevents the resurrection.
     */
    private function detachFromMasterCollection(object $entityToDelete, array $interface): void
    {
        $masterField = !empty($interface['masterField']) ? $interface['masterField'] : null;
        if (!$masterField) {
            return;
        }

        $em = $this->coreLocator->em();
        $metadata = $em->getClassMetadata(get_class($entityToDelete));
        if (!$metadata->hasAssociation($masterField)) {
            return;
        }

        $mapping = $metadata->getAssociationMapping($masterField);
        $inversedBy = is_array($mapping) ? ($mapping['inversedBy'] ?? null) : ($mapping->inversedBy ?? null);
        if (!$inversedBy) {
            return;
        }

        $parentGetter = 'get'.ucfirst($masterField);
        $parent = method_exists($entityToDelete, $parentGetter) ? $entityToDelete->$parentGetter() : null;
        if (!is_object($parent)) {
            return;
        }

        $collectionGetter = 'get'.ucfirst($inversedBy);
        if (!method_exists($parent, $collectionGetter)) {
            return;
        }

        $collection = $parent->$collectionGetter();
        if ($collection instanceof Collection && $collection->contains($entityToDelete)) {
            $collection->removeElement($entityToDelete);
        }
    }

    /**
     * Set others levels.
     */
    private function levels(mixed $entities, mixed $entityToDelete): void
    {
        foreach ($entities as $entity) {
            $haveParent = method_exists($entity, 'getParent') && method_exists($entityToDelete, 'getParent');
            if ($haveParent && $entity->getPosition() > $entityToDelete->getPosition()
                && $entity->getLevel() === $entityToDelete->getLevel()
                && $entity->getParent() === $entityToDelete->getParent()) {
                $entity->setPosition($entity->getPosition() - 1);
                $this->coreLocator->em()->persist($entity);
            }
        }
    }

    /**
     * Set others positions.
     */
    private function positions(mixed $entities, mixed $entityToDelete): void
    {
        foreach ($entities as $entity) {
            if (method_exists($entity, 'getPosition')) {
                if ($entity->getPosition() > $entityToDelete->getPosition()) {
                    $entity->setPosition($entity->getPosition() - 1);
                    $this->coreLocator->em()->persist($entity);
                }
            }
        }
    }
}
