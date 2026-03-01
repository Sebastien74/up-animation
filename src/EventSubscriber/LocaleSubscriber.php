<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Core\Website;
use App\Model\Core\ConfigurationModel;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * LocaleSubscriber.
 *
 * User locale subscriber
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    private ?string $host = null;
    private ?string $routeName = null;
    private bool $inAdmin;
    private ?WebsiteModel $website = null;
    private ?object $domain = null;

    /**
     * LocaleSubscriber constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly string $defaultLocale,
    ) {
    }

    /**
     * onKernelRequest.
     *
     * @throws ReflectionException|InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $this->routeName = $request->attributes->get('_route');

        if (!$event->isMainRequest() || !$this->isMainRequest()) {
            return;
        }

        $uri = $request->getUri();
        $this->host = $request->getHost();
        $this->inAdmin = $this->coreLocator->inAdmin();
        $session = $request->getSession();
        $asSwitch = !empty($request->query->get('_switch_user'));

        if (!$request->hasPreviousSession() && !$event->isMainRequest() || $asSwitch || str_contains($uri, '_fragment') || str_contains($uri, '_wdt')) {
            return;
        }

        /* Front request */
        if (!$this->inAdmin) {
            $this->setWebsite();
            $configuration = $this->website->configuration;
            $domain = $configuration->domain ?? $this->domain;
            $locale = $request->getPreferredLanguage($configuration->allLocales) ?? $this->defaultLocale;
            $locale = $domain ? $domain->locale : ($configuration instanceof ConfigurationModel ? $configuration->locale : $locale);
            $session->set('_locale', $locale);
            $request->setLocale($locale);
        } /* Try to see if the locale has been set as a _locale routing parameter */
        elseif ($locale = $request->attributes->get('_locale')) {
            $session->set('_locale', $locale);
        } /* If no explicit locale has been set on this request, use one from the session */
        else {
            $token = $this->coreLocator->tokenStorage()->getToken();
            if (!empty($token)) {
                $user = $token->getUser();
                if ($user && method_exists($user, 'getLocale') && $user->getLocale()) {
                    $session->set('_locale', $user->getLocale());
                }
            }
        }

        $this->setTimezone();
    }

    /**
     * Check if is mainRequest.
     *
     * @throws InvalidArgumentException
     */
    private function isMainRequest(): bool
    {
        $excludedRoutes = [
            '_wdt',
        ];

        if (in_array($this->routeName, $excludedRoutes)) {
            return false;
        }

        $filesystem = new Filesystem();
        $dirname = $this->coreLocator->cacheDir().'/routes.cache';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        if ($filesystem->exists($dirname)) {
            $cache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
            $item = $cache->getItem('route.'.$this->routeName);
            if ($item->isHit() && !$item->get()['isMainRequest']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set WebsiteModel.
     */
    private function setWebsite(): void
    {
        if (!$this->inAdmin) {
            $this->website = $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($this->host);
        }
    }

    /**
     * To set to Timezone.
     */
    public static function setTimezone(): void
    {
        $locale = \Locale::getDefault();
        if ($locale && str_contains($locale, '_')) {
            $matches = explode('_', $locale);
            $locale = $matches[0];
        }
        $timezones = $locale ? \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, strtoupper($locale)) : null;
        $timeZone = !empty($timezones[0]) ? $timezones[0] : null;
        if ($timeZone) {
            date_default_timezone_set($timeZone);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            /* must be registered before (i.e. with a higher priority than) the default Locale listener */
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
