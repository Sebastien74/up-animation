<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Service\Content;
use App\Service\Core;
use App\Service\Core\InterfaceHelper;
use App\Service\Doctrine\QueryServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Random\RandomException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension;

/**
 * CoreLocator.
 *
 * To load base Services
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CoreLocator::class, 'key' => 'core_locator'],
])]
class CoreLocator implements CoreLocatorInterface
{
    private const array ALLOWED_IPS = ['176.135.112.19', '2001:861:43c3:ce70:448f:74b:e526:cdae', '2001:861:43c3:ce70:60b8:f71:1c9:4843'];
    private array $cache = [];

    /**
     * CoreLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Content\SeoService::class, indexAttribute: 'key')] protected ServiceLocator $seoLocator,
        #[AutowireLocator(Core\TreeService::class, indexAttribute: 'key')] protected ServiceLocator $treeLocator,
        #[AutowireLocator(Content\ListingService::class, indexAttribute: 'key')] protected ServiceLocator $listingLocator,
        #[AutowireLocator(Content\ThumbService::class, indexAttribute: 'key')] protected ServiceLocator $thumbLocator,
        #[AutowireLocator(InterfaceHelper::class, indexAttribute: 'key')] protected ServiceLocator $interfaceLocator,
        #[AutowireLocator(Core\LastRouteService::class, indexAttribute: 'key')] protected ServiceLocator $lastRouteLocator,
        #[AutowireLocator(Content\RedirectionService::class, indexAttribute: 'key')] protected ServiceLocator $redirectionLocator,
        #[AutowireLocator(Content\MarkdownServiceInterface::class, indexAttribute: 'key')] protected ServiceLocator $markdownLocator,
        #[AutowireLocator(Core\FileInfo::class, indexAttribute: 'key')] protected ServiceLocator $fileLocator,
        private readonly EntryFilesTwigExtension $entryFiles,
        private readonly Core\CacheServiceInterface $cacheService,
        private readonly QueryServiceInterface $queryService,
        private readonly HttpFoundation\RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RouterInterface $router,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly string $projectDir,
        private readonly string $cacheDir,
        private readonly string $logDir,
        private readonly bool $isDebug,
    ) {
    }

    /**
     * To get a website model.
     */
    public function website(): ?WebsiteModel
    {
        if (
            ($this->request() && preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $this->request()->getUri()))
            || ($this->request() && preg_match('/\/preview\//', $this->request()->getUri()))
        ) {
            if (!empty($this->cache['adminWebsite'])) {
                return $this->cache['adminWebsite'];
            }
            $websiteRequest = $this->request()->attributes->get('website') ?? $this->request()->query->get('website');
            $websiteId = filter_var($websiteRequest, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $this->cache['adminWebsite'] = $websiteId
                ? $this->em()->getRepository(Website::class)->findObject($websiteId)
                : $this->em()->getRepository(Website::class)->findOneByHost($this->request()->getHost());
            if (!is_object($this->cache['adminWebsite'])) {
                throw new NotFoundHttpException('Aucun site configuré pour cet ID.');
            }
            return $this->cache['adminWebsite'];
        } elseif ($this->request()) {
            if (!empty($this->cache['frontWebsite'])) {
                return $this->cache['frontWebsite'];
            }
            $this->cache['frontWebsite'] = $this->em()->getRepository(Website::class)->findOneByHost($this->request()->getHost());
            return $this->cache['frontWebsite'];
        }

        return null;
    }

    /**
     * To call SeoInterface.
     *
     * @throws ContainerExceptionInterface
     */
    public function seoService(): Content\SeoService
    {
        return $this->seoLocator->get('seo_service');
    }

    /**
     * To call TreeService.
     *
     * @throws ContainerExceptionInterface
     */
    public function treeService(): Core\TreeService
    {
        return $this->treeLocator->get('tree_service');
    }

    /**
     * To call ListingService.
     *
     * @throws ContainerExceptionInterface
     */
    public function listingService(): Content\ListingService
    {
        return $this->listingLocator->get('listing_service');
    }

    /**
     * To call ThumbService.
     *
     * @throws ContainerExceptionInterface
     */
    public function thumbService(): Content\ThumbService
    {
        return $this->thumbLocator->get('thumb_service');
    }

    /**
     * To call InterfaceHelper.
     *
     * @throws ContainerExceptionInterface
     */
    public function interfaceHelper(): InterfaceHelper
    {
        return $this->interfaceLocator->get('interface_helper');
    }

    /**
     * To call CacheServiceInterface.
     */
    public function cacheService(): Core\CacheServiceInterface
    {
        return $this->cacheService;
    }

    /**
     * To get RequestStack.
     */
    public function requestStack(): HttpFoundation\RequestStack
    {
        return $this->requestStack;
    }

    /**
     * To get Request.
     */
    public function request(): ?HttpFoundation\Request
    {
        return $this->requestStack->getMainRequest();
    }

    /**
     * To get Request.
     */
    public function currentRequest(): ?HttpFoundation\Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * To get schemeAndHttpHost.
     */
    public function schemeAndHttpHost(): ?string
    {
        return $this->request() ? $this->request()->getSchemeAndHttpHost() : null;
    }

    /**
     * To get locale.
     */
    public function locale(): ?string
    {
        return $this->request() ? $this->request()->getLocale() : 'fr';
    }

    /**
     * To check if the url is in front render.
     */
    public function inFront(): bool
    {
        return !$this->inAdmin() && !$this->inSecurity();
    }

    /**
     * To get ADMIN_THEME.
     */
    public function adminTheme(): string
    {
        $request = $this->request();
        $theme = $request->cookies->get('ADMIN_THEME');

        if (!$theme) {
            $session = $request->hasSession() ? $request->getSession() : null;
            $theme = $session?->get('ADMIN_THEME');
        }

        if ($theme !== 'light' && $theme !== 'dark') {
            $theme = 'dark';
        }

        return $theme;
    }

    /**
     * To get FRONT_THEME.
     */
    public function frontTheme(): string
    {
        $request = $this->request();
        $theme = $request->cookies->get('FRONT_THEME');

        if (!$theme) {
            $session = $request->hasSession() ? $request->getSession() : null;
            $theme = $session?->get('FRONT_THEME');
        }

        if ($theme !== 'light' && $theme !== 'dark') {
            $theme = 'light';
        }

        return $theme;
    }

    /**
     * To check if the url is in admin render.
     */
    public function inAdmin(): bool
    {
        $uri = $this->request() instanceof HttpFoundation\Request ? $this->request()->getUri() : false;
        return $uri && preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $uri)
            && !str_contains($uri, '/preview/');
    }

    /**
     * To check if the url is in security, render.
     */
    public function inSecurity(): bool
    {
        $uri = $this->request() instanceof HttpFoundation\Request ? $this->request()->getUri() : false;
        return $uri && str_contains($uri, '/secure/user');
    }

    /**
     * To get TranslatorInterface.
     */
    public function translator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * To get EntityManagerInterface.
     */
    public function entityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * To get EntityManagerInterface.
     */
    public function em(): EntityManagerInterface
    {
        return $this->entityManager();
    }

    /**
     * To get RouterInterface.
     */
    public function router(): RouterInterface
    {
        return $this->router;
    }

    /**
     * To get route args to generate route.
     *
     * @throws NonUniqueResultException|ContainerExceptionInterface
     */
    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array
    {
        if ($route) {
            $routeInfos = $this->router()->getRouteCollection()->get($route);
            if ($routeInfos) {
                preg_match_all('/\{([^}]*)\}/', $routeInfos->getPath(), $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $match) {
                        if (empty($parameters[$match])) {
                            if ($this->request()->get($match) && is_numeric($this->request()->get($match))) {
                                $parameters[$match] = intval($this->request()->get($match));
                            } elseif ($entity && is_object($entity) && method_exists($entity, 'getId')) {
                                $interface = $this->interfaceHelper()->generate(get_class($entity));
                                if (!empty($interface['name']) && $match === $interface['name']) {
                                    $parameters[$match] = $entity->getId();
                                }
                            } elseif ($this->request()->attributes->get('interfaceName')
                                && $this->request()->attributes->get('interfaceEntity')
                                && $match === $this->request()->attributes->get('interfaceName')) {
                                $parameters[$match] = $this->request()->attributes->get('interfaceEntity');
                            } elseif ($this->request()->attributes->get('entitylocale') && 'entitylocale' === $match) {
                                $parameters[$match] = $this->request()->attributes->get('entitylocale');
                            }
                        }
                    }
                }
            }
        }

        return $parameters;
    }

    /**
     * To log in messages.json
     *
     * @throws \DateMalformedStringException|\DateInvalidOperationException
     */
    public function jsonLog(string $text, string $type = 'critical', string $filename = 'critical'): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'log';
        $logDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logDir);
        $filepath = $logDir . DIRECTORY_SEPARATOR . $filename . '.json';

        // Create a directory if it does not exist.
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $format = 'Y-m-d H:i:s';
        $tz = new \DateTimeZone('Europe/Paris');
        $now = new \DateTimeImmutable('now', $tz);
        $today = $now->format('Y-m-d');

        // Load existing messages (or empty array).
        $messages = file_exists($filepath)
            ? (json_decode(file_get_contents($filepath), true) ?: [])
            : [];

        $threshold = $now->sub(new \DateInterval('P15D'));

        $filteredMessages = [];
        $duplicateForToday = false;

        // Clean old entries and check duplicate for today.
        foreach ($messages as $date => $msg) {
            $d = \DateTimeImmutable::createFromFormat($format, $date, $tz);

            // Skip invalid or too old entries.
            if (!$d || $d < $threshold) {
                continue;
            }

            // Check duplicate for the same day, same type, same message.
            if (
                $d->format('Y-m-d') === $today
                && \is_array($msg)
                && ($msg['type'] ?? null) === $type
                && ($msg['message'] ?? null) === $text
            ) {
                $duplicateForToday = true;
            }

            // Keep valid entry.
            $filteredMessages[$date] = $msg;
        }

        // If duplicate found for today, do not add a new entry
        if ($duplicateForToday) {
            // You can still rewrite the file with the rotation applied.
            uksort($filteredMessages, static fn(string $a, string $b): int => strcmp($b, $a));
            file_put_contents($filepath, json_encode($filteredMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        // Add the current message.
        $filteredMessages[$now->format($format)] = [
            'type' => $type,
            'message' => $text,
        ];

        // Sort keys (dates) in descending order.
        uksort($filteredMessages, static fn(string $a, string $b): int => strcmp($b, $a));

        // Save JSON.
        file_put_contents($filepath, json_encode($filteredMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * To get TokenStorageInterface.
     */
    public function tokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }

    /**
     * To get the current User.
     */
    public function user(): ?UserInterface
    {
        if (!empty($this->tokenStorage->getToken())) {
            return $this->tokenStorage->getToken()->getUser();
        }

        return null;
    }

    /**
     * To get AuthorizationCheckerInterface.
     */
    public function authorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }

    /**
     * To get LastRouteService.
     *
     * @throws ContainerExceptionInterface
     */
    public function lastRoute(): Core\LastRouteService
    {
        return $this->lastRouteLocator->get('last_route_service');
    }

    /**
     * To get RedirectionService.
     *
     * @throws ContainerExceptionInterface
     */
    public function redirectionService(): Content\RedirectionService
    {
        return $this->redirectionLocator->get('redirection_service');
    }

    /**
     * To get FileInfo.
     *
     * @throws ContainerExceptionInterface
     */
    public function fileInfo(): Core\FileInfo
    {
        return $this->fileLocator->get('file_info_service');
    }

    /**
     * To get QueryService.
     */
    public function emQuery(): QueryServiceInterface
    {
        return $this->queryService;
    }

    /**
     * To set Xss Protection Data.
     */
    public function XssProtectionData(mixed $value = null): string|array|null
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (!is_string($val) || !preg_match('/^[\p{L}\p{N} _\-.,\'"]+$/u', $val)) {
                    $value[$key] = null;
                }
            }
            return $value;
        }

        if (!is_string($value) || !preg_match('/^[\p{L}\p{N} _\-.,\'"]+$/u', $value)) {
            $value = null;
        }

        return $value;
    }

    /**
     * To get metadata.
     */
    public function metadata(mixed $entity, string $fieldName, bool $instanceof = false): object|bool
    {
        if ($entity) {
            $metadata = $this->entityManager->getClassMetadata(get_class($entity));
            $metadata = $metadata->getAssociationMappings();
            $metadata = !empty($metadata[$fieldName]) ? $metadata[$fieldName] : [];
        }

        if ($instanceof) {
            return !empty($metadata['targetEntity']);
        }

        return (object) [
            'targetEntity' => !empty($metadata['targetEntity']) ? $metadata['targetEntity'] : null,
            'mappedBy' => !empty($metadata['mappedBy']) ? $metadata['mappedBy'] : null,
            'setter' => !empty($metadata['mappedBy']) ? 'set'.ucfirst($metadata['mappedBy']) : null,
            'getter' => !empty($metadata['mappedBy']) ? 'get'.ucfirst($metadata['mappedBy']) : null,
            'sourceEntity' => !empty($metadata['sourceEntity']) ? $metadata['sourceEntity'] : null,
        ];
    }

    /**
     * To get markdown service.
     */
    public function markdown(?string $string = null): Content\MarkdownServiceInterface
    {
        return $this->markdownLocator->get('markdown_service');
    }

    /**
     * To check if a file exists.
     */
    public function fileExist(?string $path = null, string $dir = '/templates/'): bool
    {
        if (!$path) {
            return false;
        }

        $fileDir = '/templates/' !== $dir ? '/public/'.$path : $dir.$path;
        $fileDir = str_replace('//', '/', $fileDir);
        $fileDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDir);

        try {
            $filesystem = new Filesystem();
            return $filesystem->exists($this->projectDir.$fileDir);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * To check if a route exists.
     *
     * @throws InvalidArgumentException
     */
    public function routeExist(?string $routeName): bool
    {
        if (!$routeName) {
            return false;
        }

        if (!empty($this->cache['routes'])) {
            return array_key_exists($routeName, $this->cache['routes']) || array_key_exists('route.'.$routeName, $this->cache['routes']);
        }

        $filesystem = new Filesystem();
        $dirname = $this->formatDirname($this->cacheDir . '/routes.cache');
        if ($filesystem->exists($dirname)) {
            $raw = require $dirname;
            $map = $raw[0] ?? [];
            foreach ($map as $name => $isMainRequest) {
                $this->cache['routes'][$name] = $isMainRequest;
            }
        }

        return array_key_exists($routeName, $this->cache['routes']) || array_key_exists('route.'.$routeName, $this->cache['routes']);
    }

    /**
     * To check IP.
     */
    public function checkIP(?WebsiteModel $website = null): bool
    {
        $websiteIps = $website ? $website->configuration->ipsDev : (new Configuration())->getIpsDev();
        $allowedIps = array_unique(array_merge(self::ALLOWED_IPS, $websiteIps));

        return (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($_SERVER['HTTP_X_FORWARDED_FOR'], $allowedIps, true))
            || (isset($_SERVER['HTTP_X_REAL_IP']) && in_array($_SERVER['HTTP_X_REAL_IP'], $allowedIps, true))
            || in_array(@$_SERVER['REMOTE_ADDR'], $allowedIps, true);
    }

    /**
     * To check if a route exists.
     *
     * @throws InvalidArgumentException
     */
    public function checkRoute(string $routeName): bool
    {
        $dirname = $this->cacheDir.'/routes.cache';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $cache = new PhpArrayAdapter($dirname, new FilesystemAdapter());

        return $cache->getItem('route.'.$routeName)->isHit();
    }

    /**
     * Generate a random alphanumeric string.
     *
     * @throws RandomException
     */
    public function alphanumericKey(int $length = 15): ?string
    {
        $length = min($length, 255);
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $index = random_int(0, $charactersLength - 1); // Cryptographically secure
            $randomString .= $characters[$index];
        }

        return $randomString;
    }


    /**
     * To escape string.
     */
    public function unescape(?string $string = null): ?string
    {
        if (!$string) {
            return null;
        }

        $whitespacesChars = [
            '?' => 'l', '!' => 'l', ':' => 'l',
            '"' => 'rl', "'" => 'rl',
            '«' => 'r', '»' => 'l'
        ];

        // Split into tags and text segments
        $parts = preg_split('/(<[^>]+>)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $i => $part) {
            // Skip HTML tags
            if (preg_match('/^<[^>]+>$/', $part)) {
                continue;
            }
            // Process only text segments
            foreach ($whitespacesChars as $char => $position) {
                if (str_contains($position, 'l')) {
                    $part = preg_replace('/\s+(' . preg_quote($char, '/') . ')/', '&nbsp;$1', $part);
                }
                if (str_contains($position, 'r')) {
                    $part = preg_replace('/(' . preg_quote($char, '/') . ')\s+/', '$1&nbsp;', $part);
                }
            }
            $parts[$i] = $part;
        }

        return implode('', $parts);
    }

    /**
     * To get preload Files.
     */
    public function preloadFiles(): array
    {
        if (!empty($this->cache['preloads'])) {
            return $this->cache['preloads'];
        }

        $preloads = [];
        $template = Core\Urlizer::urlize(($this->website()->configuration->template));
        $onLoaded = $this->entryFiles->getWebpackJsFiles('front-'.$template.'-vendor', 'front_default');

        if (!empty($onLoaded[0])) {
            $preloads['js'] = [
                $this->schemeAndHttpHost().$onLoaded[0],
            ];
            $this->cache['preloads'] = $preloads;
        }

        return $preloads;
    }

    /**
     * To get projectDir.
     */
    public function formatDirname(?string $dirname = null): ?string
    {
        return $dirname ? str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname) : null;
    }

    /**
     * To get projectDir.
     */
    public function projectDir(): string
    {
        return $this->formatDirname($this->projectDir);
    }

    /**
     * To get publicDir.
     */
    public function publicDir(): string
    {
        return $this->formatDirname($this->projectDir.'/public');
    }

    /**
     * To get uploadDir.
     */
    public function uploadDir(): string
    {
        return $this->formatDirname($this->publicDir().'/uploads');
    }

    /**
     * To get cacheDir.
     */
    public function cacheDir(): string
    {
        return $this->formatDirname($this->cacheDir);
    }

    /**
     * To get logDir.
     */
    public function logDir(): string
    {
        return $this->formatDirname($this->logDir);
    }

    /**
     * To get isDebug.
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * To get isProd.
     */
    public function isProd(): bool
    {
        return $this->envName() === 'prod';
    }

    /**
     * To get envName.
     */
    public function envName(): string
    {
        return $_ENV['APP_ENV'];
    }
}
