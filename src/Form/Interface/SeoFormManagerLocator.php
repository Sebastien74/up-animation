<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Seo;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * SeoFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SeoFormManagerLocator implements SeoFormManagerInterface
{
    /**
     * SeoFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Seo\ImportRedirectionManager::class, indexAttribute: 'key')] protected ServiceLocator $importRedirectionLocator,
        #[AutowireLocator(Seo\RedirectionManager::class, indexAttribute: 'key')] protected ServiceLocator $redirectionLocator,
        #[AutowireLocator(Seo\UrlManager::class, indexAttribute: 'key')] protected ServiceLocator $urlLocator,
    ) {
    }

    /**
     * To get ImportRedirectionManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function importRedirection(): Seo\ImportRedirectionManager
    {
        return $this->importRedirectionLocator->get('seo_import_redirection_form_manager');
    }

    /**
     * To get RedirectionManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function redirection(): Seo\RedirectionManager
    {
        return $this->redirectionLocator->get('seo_redirection_form_manager');
    }

    /**
     * To get RedirectionManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function url(): Seo\UrlManager
    {
        return $this->redirectionLocator->get('seo_url_form_manager');
    }
}