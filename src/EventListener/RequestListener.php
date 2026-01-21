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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * RequestListener.
 *
 * Listen front events
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

        $this->request = $event->getRequest();
        $this->routeName = $this->request->attributes->get('_route');

        if (!$this->isMainRequest() || $this->isSubRequest()) {
            return;
        }

        $this->event = $event;
        $this->session = $this->request->getSession();
        $this->uri = $this->request->getUri();
        $requestUri = $this->request->getRequestUri();
        $asIndexFile = 'index.php' === str_replace('/', '', $requestUri);

        if ($asIndexFile) {
            $this->event->setResponse(new RedirectResponse($this->request->getSchemeAndHttpHost(), 301));
            return;
        }

        $securityToken = $_ENV['SECURITY_TOKEN'] ?? '';
        $isAdminPath = str_contains($this->uri, '/admin-' . $securityToken . '/');
        $isLogin = str_contains($this->uri, '/secure/user');
        $isPreview = str_contains($this->uri, '/preview/');
        $isFront = (!$isAdminPath && !$isLogin) || $isPreview;

        $this->website = $this->coreLocator->website();
        $this->coreLocator->lastRoute()->execute($event);
        $this->coreLocator->cacheService()->generateRoutes();
        $this->session->remove('mainExceptionMessage');

        if ($isFront) {
            $this->checkDisabledUris();
            if (!$this->event->getResponse()) {
                $this->frontRequest();
            }
        } elseif (!$isLogin) {
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
     * Check if is mainRequest.
     *
     * @throws InvalidArgumentException
     */
    private function isMainRequest(): bool
    {
        $excludedRoutes = ['_wdt' => true, '_fragment' => true, '_profiler' => true];
        if (isset($excludedRoutes[$this->routeName])) {
            return false;
        }

        $uri = $this->request->getUri();
        if (str_contains($uri, '_wdt') || str_contains($uri, '_profiler')) {
            return false;
        }

        if (str_contains($uri, '_fragment') && str_contains($uri, '_hash')) {
            return false;
        }

        if (self::$routesCache === null) {
            $dirname = $this->coreLocator->cacheDir() . DIRECTORY_SEPARATOR . 'routes.cache';
            if (file_exists($dirname)) {
                $cache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
                self::$routesCache = $cache->getItem('routes.list')->get() ?? [];
            } else {
                self::$routesCache = [];
            }
        }

        if (isset(self::$routesCache[$this->routeName]) && !self::$routesCache[$this->routeName]['isMainRequest']) {
            return false;
        }

        return true;
    }

    /**
     * Check if is disabled URI.
     */
    private function checkDisabledUris(): void
    {
        if ($this->uri && preg_match('/wordpress|wp-includes|wp-admin|autodiscover/i', $this->uri)) {
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
        $asAccessibility = $this->request->get('user_accessibility') || $this->request->get('user_accessibility_initial');
        if ($asAccessibility) {
            $status = true === (bool)$this->request->get('user_accessibility') ? '1' : '0';
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

        if ($this->request->get('admin_dark_theme') || $this->request->get('admin_dark_theme_initial')) {
            $response = new RedirectResponse($this->request->getPathInfo());
            $expire = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->modify('+365 days');
            $response->headers->setCookie(Cookie::create('ADMIN_DARK_THEME', !empty($this->request->get('admin_dark_theme')) ? '1' : '0', $expire));
            $this->event->setResponse($response);
            return;
        }

        if (!$_FILES && $this->request->get('entitylocale')) {
            $this->session->set('currentEntityLocale', $this->request->query->get('entitylocale'));
        }
    }
}
