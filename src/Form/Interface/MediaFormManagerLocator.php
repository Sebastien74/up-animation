<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Media;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * MediaFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaFormManagerLocator implements MediaFormManagerInterface
{
    /**
     * MediaFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Media\MediaLibraryManager::class, indexAttribute: 'key')] protected ServiceLocator $libraryLocator,
        #[AutowireLocator(Media\MediaManager::class, indexAttribute: 'key')] protected ServiceLocator $mediaLocator,
        #[AutowireLocator(Media\ModalLibraryManager::class, indexAttribute: 'key')] protected ServiceLocator $modalLibraryLocator,
        #[AutowireLocator(Media\SearchManager::class, indexAttribute: 'key')] protected ServiceLocator $searchLocator,
    ) {
    }

    /**
     * To get MediaLibraryManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function library(): Media\MediaLibraryManager
    {
        return $this->libraryLocator->get('media_library_form_manager');
    }

    /**
     * To get MediaManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function media(): Media\MediaManager
    {
        return $this->mediaLocator->get('media_form_manager');
    }

    /**
     * To get ModalLibraryManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function modalLibrary(): Media\ModalLibraryManager
    {
        return $this->modalLibraryLocator->get('media_modal_library_form_manager');
    }

    /**
     * To get SearchManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function search(): Media\SearchManager
    {
        return $this->searchLocator->get('media_search_form_manager');
    }
}