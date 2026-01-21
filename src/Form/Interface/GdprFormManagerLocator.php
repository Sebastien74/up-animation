<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Gdpr\CookieManager;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * GdprFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GdprFormManagerLocator implements GdprFormManagerInterface
{
    /**
     * ApiFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(CookieManager::class, indexAttribute: 'key')] protected ServiceLocator $cookieLocator,
    ) {
    }

    /**
     * To get CustomManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function cookie(): CookieManager
    {
        return $this->cookieLocator->get('gdpr_cookie_form_manager');
    }
}
