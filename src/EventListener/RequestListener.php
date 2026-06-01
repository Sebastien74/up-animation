<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Security\Interface\UserCheckerInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * RequestListener.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RequestListener
{
    private RequestEvent $event;
    private ?Request $request = null;
    private ?SessionInterface $session;
    private ?WebsiteModel $website = null;
    private ?string $uri = null;
    private ?string $routeName = null;

    private static ?array $routesCache = null;

    /**
     * RequestListener constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly UserCheckerInterface $userChecker,
    ) {
    }

    /**
     * onKernelRequest.
     *
     * @throws NonUniqueResultException|Exception|InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->event = $event;
        $this->request = $event->getRequest();
        $this->routeName = $this->request->attributes->get('_route');
        $this->uri = $this->request->getUri();

        if (!$this->isMainRequest() || $this->isSubRequest()) {
            return;
        }

        $requestUri = $this->request->getRequestUri();
        if ('/index.php' === $requestUri || 'index.php' === $requestUri) {
            $this->event->setResponse(new RedirectResponse($this->request->getSchemeAndHttpHost(), 301));
            return;
        }

        $this->session = $this->request->getSession();
        $this->website = $this->coreLocator->website();

        if ($this->request->query->has('simule_error') && ($this->coreLocator->isDebug() || $this->coreLocator->checkIP($this->website))) {
            $this->event->setResponse($this->simulateError());
            return;
        }

        $this->coreLocator->lastRoute()->execute($event);
        $this->coreLocator->cacheService()->generateRoutes();
        
        if ($this->session->has('mainExceptionMessage')) {
            $this->session->remove('mainExceptionMessage');
        }

        if ($this->coreLocator->inFront()) {
            $this->checkDisabledUris();
            if (!$this->event->getResponse()) {
                $this->frontRequest();
            }
        } elseif (!$this->coreLocator->inSecurity()) {
            $this->adminRequest();
        }

        if (!$this->event->getResponse()) {
            $this->userChecker->execute($event, $this->website);
        }
    }

    /**
     * Render the standalone branded HTTP error page (dev helper, debug/allowed IP only).
     */
    private function simulateError(): Response
    {
        $statusCode = (int) $this->request->query->get('simule_error') ?: 500;
        $detail = $this->coreLocator->isDebug() ? sprintf('Simulated HTTP %d from %s', $statusCode, $this->request->getRequestUri()) : null;
        $homeUrl = $this->request->getSchemeAndHttpHost();
        $isDebug = $this->coreLocator->isDebug();

        ob_start();
        require dirname(__DIR__, 2).'/config/http_error.php';

        return new Response((string) ob_get_clean(), $statusCode);
    }

    /**
     * Check if is subRequest.
     */
    private function isSubRequest(): bool
    {
        static $subRoutes = [
            'front_render_block' => true,
            'front_encrypt' => true,
            'front_decrypt' => true,
            'front_webmaster_toolbox' => true,
            'front_gdpr_scripts' => true,
        ];

        return isset($subRoutes[$this->routeName]);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function isMainRequest(): bool
    {
        if (!$this->routeName) {
            return true;
        }

        static $excludedRoutes = ['_wdt' => true, '_fragment' => true, '_profiler' => true];
        if (isset($excludedRoutes[$this->routeName])) {
            return false;
        }

        if (str_contains($this->uri, '_wdt') || str_contains($this->uri, '_profiler')) {
            return false;
        }

        if (str_contains($this->uri, '_fragment') && str_contains($this->uri, '_hash')) {
            return false;
        }

        if (self::$routesCache === null) {
            $cacheFile = $this->coreLocator->cacheDir() . DIRECTORY_SEPARATOR . 'routes.cache';
            if (file_exists($cacheFile)) {
                $cache = new PhpArrayAdapter($cacheFile, new FilesystemAdapter());
                $item = $cache->getItem('routes.list');
                self::$routesCache = $item->isHit() ? $item->get() : [];
            } else {
                self::$routesCache = [];
            }
        }

        if ((isset(self::$routesCache[$this->routeName]) && !self::$routesCache[$this->routeName]['isMainRequest'])
            || (isset(self::$routesCache['route.' . $this->routeName]) && !self::$routesCache['route.' . $this->routeName]['isMainRequest'])) {
            return false;
        }

        return true;
    }

    private function checkDisabledUris(): void
    {
        if ($this->uri && (
            str_contains($this->uri, 'wordpress') ||
            str_contains($this->uri, 'wp-includes') ||
            str_contains($this->uri, 'wp-admin') ||
            str_contains($this->uri, 'autodiscover')
        )) {
            $this->event->setResponse(new RedirectResponse($this->request->getSchemeAndHttpHost(), 301));
        }
    }

    /**
     * Check front Request.
     *
     * @throws NonUniqueResultException|InvalidArgumentException|MappingException
     */
    private function frontRequest(): void
    {
        if ($this->request->query->has('front_light_theme') || $this->request->query->has('front_dark_theme')) {

            $status = $this->request->query->has('front_dark_theme') ? 'dark' : 'light';
            $expire = new \DateTimeImmutable('+1 year');
            $this->session?->set('FRONT_THEME', $status);

            $query = $this->request->query->all();
            unset($query['front_light_theme'], $query['front_dark_theme']);

            $baseUrl = $this->request->getSchemeAndHttpHost() . $this->request->getBaseUrl() . $this->request->getPathInfo();
            $redirectUrl = $query ? $baseUrl . '?' . http_build_query($query) : $baseUrl;

            $response = new RedirectResponse($redirectUrl);
            $response->headers->setCookie(Cookie::create('FRONT_THEME', $status, $expire, '/', null, false, true, false, 'lax'));
            $this->event->setResponse($response);
            return;
        }

        $asAccessibility = $this->request->query->get('user_accessibility') || $this->request->query->get('user_accessibility_initial');
        if ($asAccessibility) {
            $status = true === (bool)$this->request->query->get('user_accessibility') ? '1' : '0';
            $response = new RedirectResponse($this->request->getPathInfo());
            $response->headers->setCookie(Cookie::create('USER_ACCESSIBILITY',
                $status,
                new \DateTimeImmutable('+30 days'),
                '/',
                null,
                true,
                true,
                false,
                'lax'
            ));
            $this->event->setResponse($response);
            return;
        }

        if ('login' === trim($this->request->getRequestUri(), '/') && $this->coreLocator->checkIP($this->website)) {
            $this->event->setResponse(new RedirectResponse($this->coreLocator->router()->generate('security_login')));
        } else {
            $response = $this->coreLocator->redirectionService()->execute($this->request);
            if ($response['urlRedirection'] || $response['domainRedirection'] || $response['inBuildRedirection'] || $response['banRedirection']) {
                $url = $response['urlRedirection'] ?: ($response['domainRedirection'] ?: ($response['inBuildRedirection'] ?: $response['banRedirection']));
                $status = ($response['inBuildRedirection'] || $response['banRedirection']) ? 302 : 301;
                $this->event->setResponse(new RedirectResponse($url, $status));
            }
            $this->website = $response['website'];
        }
    }

    /**
     * Check admin Request.
     *
     * @throws Exception
     */
    private function adminRequest(): void
    {
        $websiteRequest = $this->request->attributes->get('website');
        $repository = $this->coreLocator->em()->getRepository(Website::class);
        $website = is_numeric($websiteRequest) ? $repository->findByIdForAdmin(intval($websiteRequest)) : $repository->findDefault();

        if (!$website) {
            $website = $repository->findDefault();
            if ($website) {
                $this->event->setResponse(new RedirectResponse($this->coreLocator->router()->generate('admin_dashboard', ['website' => $website->id]), 302));
                return;
            }
        }

        if ($this->request->query->has('admin_light_theme') || $this->request->query->has('admin_dark_theme')) {

            $status = $this->request->query->has('admin_dark_theme') ? 'dark' : 'light';
            $expire = new \DateTime()->modify('+1 year');
            $this->session?->set('ADMIN_THEME', $status);

            $query = $this->request->query->all();
            unset($query['admin_light_theme'], $query['admin_dark_theme']);
            
            $baseUrl = $this->request->getSchemeAndHttpHost() . $this->request->getBaseUrl() . $this->request->getPathInfo();
            $redirectUrl = $query ? $baseUrl . '?' . http_build_query($query) : $baseUrl;

            $response = new RedirectResponse($redirectUrl);
            $response->headers->setCookie(new Cookie('ADMIN_THEME', $status, $expire, '/', null, false, true, false, 'lax'));
            $this->event->setResponse($response);
            return;
        }

        $entityLocale = $this->request->query->get('entitylocale') ? $this->request->query->get('entitylocale') : $this->request->attributes->get('entitylocale');
        if (!$this->request->files->count() && $entityLocale) {
            $this->session->set('currentEntityLocale', $entityLocale);
        }
    }
}
