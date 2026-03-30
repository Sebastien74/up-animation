<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Core\Website;
use App\Model\Core\ConfigurationModel;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * LocaleSubscriber.
 *
 * Resolve and apply the request locale.
 */
readonly class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * LocaleSubscriber constructor.
     */
    public function __construct(
        private CoreLocatorInterface $coreLocator,
        private string $defaultLocale,
    ) {
    }

    /**
     * Handle the main request locale resolution.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        $uri = $request->getUri();

        if (
            '_wdt' === $routeName
            || str_contains($uri, '_fragment')
            || str_contains($uri, '_wdt')
            || $request->query->has('_switch_user')
        ) {
            return;
        }

        $session = $request->getSession();
        $inAdmin = $this->coreLocator->inAdmin();

        if (!$inAdmin) {
            $this->applyFrontLocale($request, $session);
        } elseif ($locale = $request->attributes->get('_locale')) {
            $session->set('_locale', $locale);
            $request->setLocale($locale);
        } else {
            $this->applyUserLocale($request, $session);
        }
    }

    /**
     * Apply locale for front requests.
     */
    private function applyFrontLocale(object $request, object $session): void
    {
        $website = $this->coreLocator->website();

        if (!$website) {
            $request->setLocale($this->defaultLocale);
            $session->set('_locale', $this->defaultLocale);
            return;
        }

        $configuration = $website->configuration;
        $domain = $configuration->domain ?? null;

        $locale = $request->getPreferredLanguage($configuration->allLocales) ?? $this->defaultLocale;
        $locale = $domain?->locale
            ?? ($configuration instanceof ConfigurationModel ? $configuration->locale : $locale)
            ?? $this->defaultLocale;

        $session->set('_locale', $locale);
        $request->setLocale($locale);
    }

    /**
     * Apply locale from the authenticated user when available.
     */
    private function applyUserLocale(object $request, object $session): void
    {
        $token = $this->coreLocator->tokenStorage()->getToken();

        if (!$token) {
            return;
        }

        $user = $token->getUser();

        if ($user && method_exists($user, 'getLocale') && $user->getLocale()) {
            $locale = $user->getLocale();
            $session->set('_locale', $locale);
            $request->setLocale($locale);
        }
    }

    /**
     * Register subscribed events.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}