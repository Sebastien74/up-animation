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

        if ($this->request->query->get('admin_light_theme') || $this->request->query->get('admin_dark_theme')) {
            $response = new RedirectResponse($this->request->getPathInfo());
            $expire = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->modify('+365 days');
            $response->headers->setCookie(Cookie::create('ADMIN_LIGHT_THEME', !empty($this->request->query->get('admin_light_theme')) ? '1' : '0', $expire));
            $this->event->setResponse($response);
            return;
        }

        $entityLocale = $this->request->query->get('entitylocale') ? $this->request->query->get('entitylocale') : $this->request->attributes->get('entitylocale');
        if (!$_FILES && $entityLocale) {
            $this->session->set('currentEntityLocale', $entityLocale);
        }
    }
}
