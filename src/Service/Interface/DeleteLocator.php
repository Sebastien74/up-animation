<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Admin\DeleteService;
use App\Service\Delete\ContactDeleteService;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * DeleteLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DeleteLocator implements DeleteInterface
{
    /**
     * FrontFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(DeleteService::class, indexAttribute: 'key')] protected ServiceLocator $coreDeleteLocator,
        #[AutowireLocator(ContactDeleteService::class, indexAttribute: 'key')] protected ServiceLocator $contactDeleteLocator,
    ) {
    }

    /**
     * To get DeleteService.
     *
     * @throws ContainerExceptionInterface
     */
    public function coreService(): DeleteService
    {
        return $this->coreDeleteLocator->get('core_delete_service');
    }

    /**
     * To get ContactDeleteService.
     *
     * @throws ContainerExceptionInterface
     */
    public function contactsService(): ContactDeleteService
    {
        return $this->contactDeleteLocator->get('contact_delete_service');
    }
}
