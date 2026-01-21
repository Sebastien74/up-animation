<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Information;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * InformationFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InformationFormManagerLocator implements InformationFormManagerInterface
{
    /**
     * InformationFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Information\InformationManager::class, indexAttribute: 'key')] protected ServiceLocator $informationLocator,
        #[AutowireLocator(Information\SocialNetworkManager::class, indexAttribute: 'key')] protected ServiceLocator $networksLocator,
    ) {
    }

    /**
     * To get InformationManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function information(): Information\InformationManager
    {
        return $this->informationLocator->get('info_form_manager');
    }

    /**
     * To get InformationManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function networks(): Information\SocialNetworkManager
    {
        return $this->networksLocator->get('info_networks_form_manager');
    }
}