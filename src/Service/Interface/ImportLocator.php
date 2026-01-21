<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Import;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ImportLocator implements ImportInterface
{
    /**
     * ExportLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Import\ImportProductsService::class, indexAttribute: 'key')] protected ServiceLocator $productsLocator,
    ) {
    }

    /**
     * To get ImportProductsService.
     *
     * @throws ContainerExceptionInterface
     */
    public function productsService(): Import\ImportProductsService
    {
        return $this->productsLocator->get('products_import_service');
    }
}