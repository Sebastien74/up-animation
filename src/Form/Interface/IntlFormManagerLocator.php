<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Translation;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * IntlFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IntlFormManagerLocator implements IntlFormManagerInterface
{
    /**
     * IntlFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Translation\FrontManager::class, indexAttribute: 'key')] protected ServiceLocator $frontLocator,
        #[AutowireLocator(Translation\IntlManager::class, indexAttribute: 'key')] protected ServiceLocator $intlLocator,
        #[AutowireLocator(Translation\UnitManager::class, indexAttribute: 'key')] protected ServiceLocator $unitLocator,
    ) {
    }

    /**
     * To get FrontManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function front(): Translation\FrontManager
    {
        return $this->frontLocator->get('intl_front_form_manager');
    }

    /**
     * To get IntlManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function intl(): Translation\IntlManager
    {
        return $this->intlLocator->get('intl_form_manager');
    }

    /**
     * To get UnitManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function unit(): Translation\UnitManager
    {
        return $this->unitLocator->get('intl_unit_form_manager');
    }
}
