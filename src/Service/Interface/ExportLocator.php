<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Export;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * ExportLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ExportLocator implements ExportInterface
{
    /**
     * ExportLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Export\ExportCsvService::class, indexAttribute: 'key')] protected ServiceLocator $exportLocator,
        #[AutowireLocator(Export\ExportContactService::class, indexAttribute: 'key')] protected ServiceLocator $contactsLocator,
        #[AutowireLocator(Export\ExportProductsService::class, indexAttribute: 'key')] protected ServiceLocator $productsLocator,
    ) {
    }

    /**
     * To get ExportCsvService.
     *
     * @throws ContainerExceptionInterface
     */
    public function coreService(): Export\ExportCsvService
    {
        return $this->exportLocator->get('core_export_service');
    }

    /**
     * To get ExportContactService.
     *
     * @throws ContainerExceptionInterface
     */
    public function contactsService(): Export\ExportContactService
    {
        return $this->contactsLocator->get('contacts_export_service');
    }

    /**
     * To get ExportContactService.
     *
     * @throws ContainerExceptionInterface
     */
    public function productsService(): Export\ExportProductsService
    {
        return $this->productsLocator->get('products_export_service');
    }
}
