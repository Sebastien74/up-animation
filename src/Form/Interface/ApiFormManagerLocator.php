<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Api as ApiManager;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * ApiFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ApiFormManagerLocator implements ApiFormManagerInterface
{
    /**
     * ApiFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(ApiManager\CustomManager::class, indexAttribute: 'key')] protected ServiceLocator $customLocator,
        #[AutowireLocator(ApiManager\FacebookManager::class, indexAttribute: 'key')] protected ServiceLocator $facebookLocator,
        #[AutowireLocator(ApiManager\GoogleManager::class, indexAttribute: 'key')] protected ServiceLocator $googleLocator,
        #[AutowireLocator(ApiManager\InstagramManager::class, indexAttribute: 'key')] protected ServiceLocator $instagramLocator,
    ) {
    }

    /**
     * To get CustomManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function custom(): ApiManager\CustomManager
    {
        return $this->customLocator->get('api_custom_manager');
    }

    /**
     * To get FacebookManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function facebook(): ApiManager\FacebookManager
    {
        return $this->facebookLocator->get('api_facebook_manager');
    }

    /**
     * To get GoogleManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function google(): ApiManager\GoogleManager
    {
        return $this->googleLocator->get('api_google_manager');
    }

    /**
     * To get InstagramManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function instagram(): ApiManager\InstagramManager
    {
        return $this->instagramLocator->get('api_instagram_manager');
    }
}