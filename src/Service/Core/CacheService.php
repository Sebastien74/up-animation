<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Security as SecurityEntities;
use App\Twig\Content\BrowserRuntime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * CacheService.
 *
 * Manage app cache.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CacheService implements CacheServiceInterface
{
    private const bool LOG_EVENTS = false;
    private const bool ACTIVE_FOR_DEV = false;
    private const bool CACHE_BLOCKS = false;
    private const int CACHE_EXPIRES = 0;
    private ?string $screen;
    private ?Request $request;
    private bool $cacheActive;
    private bool $isAdministrator;
    private bool $asUser;

    /**
     * CacheService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly string $cacheDir,
        private readonly string $logDir,
        private readonly BrowserRuntime $browserRuntime,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RouterInterface $router,
        private readonly bool $isDebug,
    ) {
        $this->screen = $this->browserRuntime->screen();
        $this->default();
    }

    /**
     * To set Request.
     */
    private function default(): void
    {
        $this->request = $this->requestStack->getMainRequest();
        $asPost = $this->request && $this->request->isMethod('POST');
        $this->asUser();
        $this->cacheActive = !$this->isDebug && !$asPost && !$this->asUser;

        if (self::ACTIVE_FOR_DEV && $this->isDebug && !$asPost) {
            $this->cacheActive = true;
        }
    }

    /**
     * To set or get a cache pool.
     *
     * @throws InvalidArgumentException|ReflectionException|Exception
     */
    public function cachePool(mixed $entity, string $name, string $method, mixed $response = null, mixed $parentEntity = null): mixed
    {
        $cacheActive = !$entity instanceof Block || self::CACHE_BLOCKS;
        if ($cacheActive && $this->cacheActive && $entity && !preg_match('/\?*=/', $this->request->getUri())) {
            $cacheKey = $this->cacheKey($entity, null, false);
            $cachePool = new FilesystemAdapter('', self::CACHE_EXPIRES, $this->cacheDir.'/pools-cache-'.$name);
            $keyName = $name.'-'.$entity->getId().'-'.$cacheKey;
            $parentEntity = $parentEntity && is_object($parentEntity) && method_exists($parentEntity, 'getId') ? $parentEntity
                : ($parentEntity && is_object($parentEntity) && property_exists($parentEntity, 'entity') ? $parentEntity->entity : null);
            if ($parentEntity && is_object($parentEntity) && get_class($parentEntity) !== get_class($entity)) {
                $keyName = $keyName.'-'.Urlizer::urlize(get_class($parentEntity)).'-'.$parentEntity->getId();
            }
            if ('GET' === $method) {
                if ($cachePool->hasItem($keyName)) {
                    $pageCachePool = $cachePool->getItem($keyName);
                    if ($pageCachePool->isHit()) {
                        return $pageCachePool->get();
                    }
                }
            } elseif ('GENERATE' === $method) {
                $pageCachePool = $cachePool->getItem($keyName);
                $pageCachePool->set($response);
                $cachePool->save($pageCachePool);
            }
        }

        return $response;
    }

    /**
     * Clear cache.
     */
    public function clearCaches(mixed $entity = null, bool $force = false): void
    {
        $inAdmin = preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $this->request->getUri());
        if ($inAdmin || $force) {
            $classPost = $this->request->getSession()->get('entityPostClassname');
            $classPost = null;
            $classPost1 = $classPost ? str_replace('Proxies\\__CG__\\', '', $classPost) : null;
            if ($entity && $classPost1 && $classPost && $classPost1 === $classPost) {
//                if (function_exists('opcache_reset')) {
//                    opcache_reset();
//                }
                $this->request->getSession()->remove('entityPostClassname');
            }
            $filesystem = new Filesystem();
            $finder = new Finder();
            $finder->directories()->name('pools-cache*')->in($this->cacheDir)->depth([0]);
            foreach ($finder as $file) {
                $cachePool = new FilesystemAdapter('', self::CACHE_EXPIRES, $file->getRealPath());
                $cachePool->clear();
                $filesystem->remove($file->getRealPath());
            }
        }
    }

    /**
     * To generate cache key.
     *
     * @throws Exception
     */
    public function cacheKey(mixed $entity, ?string $prefix = null, bool $generateEmpty = true): ?string
    {
        /* @doc https://twig.symfony.com/doc/3.x/tags/cache.html */
        if (empty($entity) && $generateEmpty || $this->isAdministrator && $generateEmpty || $this->isDebug && $generateEmpty) {
            return 'cache-'.uniqid('', true);
        }
        $webp = !empty($_SERVER['HTTP_ACCEPT']) && preg_match('/image\/webp/', $_SERVER['HTTP_ACCEPT']) ? 'webp' : 'original';
        $isArray = is_array($entity);
        $isObject = is_object($entity);
        $entityId = $isArray && !empty($entity['id']) ? $entity['id'] : $entity->getId();
        $updatedAt = $isArray && !empty($entity['updatedAt']) ? $entity['updatedAt'] : ($isObject && method_exists($entity, 'getUpdatedAt') ? $entity->getUpdatedAt() : null);
        $createdAt = $isArray && !empty($entity['createdAt']) ? $entity['createdAt'] : ($isObject && method_exists($entity, 'getCreatedAt') ? $entity->getCreatedAt() : null);
        $date = $updatedAt instanceof \DateTime ? $updatedAt : ($createdAt instanceof \DateTime ? $createdAt : new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $entityClassname = str_replace('Proxies\\__CG__\\', '', get_class($entity));
        $entityName = $isObject ? Urlizer::urlize($entityClassname) : uniqid();
        $entityName = $prefix ? $entityName.'-'.$prefix : $entityName;

        return $entityName.'-'.$entityId.'-'.$this->screen.'-'.Urlizer::urlize($this->request->getLocale()).'-'.$webp.'-'.$date->format('Ymdhis');
    }

    /**
     * To generate routes cache file.
     */
    public function generateRoutes(): void
    {
        $dirname = $this->cacheDir.'/routes.cache';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $filesystem = new Filesystem();
        if (!$filesystem->exists($dirname)) {
            $cacheRoutes = [];
            $routes = $this->router->getRouteCollection()->all();
            foreach ($routes as $name => $route) {
                $isMainRequest = true;
                $defaults = $route->getDefaults();
                if (isset($defaults['_controller'])) {
                    $options = is_object($route) && method_exists($route, 'getOptions') ? $route->getOptions() : [];
                    $isMainRequest = $options['isMainRequest'] ?? true;
                }
                $cacheRoutes['route.'.$name] = ['isMainRequest' => $isMainRequest];
            }
            $cache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
            $cache->warmUp($cacheRoutes);
        }
    }

    /**
     * To check if user connected.
     */
    private function asUser(): void
    {
        $token = $this->tokenStorage->getToken();
        $user = is_object($token) && method_exists($token, 'getUser') && method_exists($token->getUser(), 'getId') ? $token->getUser() : null;
        $this->isAdministrator = $user && in_array('ROLE_INTERNAL', $user->getRoles());
        $this->asUser = $user instanceof SecurityEntities\User || $user instanceof SecurityEntities\UserFront;
    }

    /**
     * Get Request WebsiteModel.
     */
    protected function getWebsite(): Website
    {
        $repository = $this->entityManager->getRepository(Website::class);
        if (preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $this->request->getUri())) {
            return $this->request->get('website')
                ? $repository->find(intval($this->request->get('website')))
                : $repository->findOneByHost($this->request->getHost());
        } else {
            return $repository->findOneByHost($this->request->getHost());
        }
    }

    /**
     * Logger.
     */
    private function logger(string $message): void
    {
        if (self::LOG_EVENTS) {
            $logger = new Logger('cache.entity');
            $logger->pushHandler(new RotatingFileHandler($this->logDir.'/cache-entity', 10, Level::Info));
            $logger->info($message);
        }
    }
}
