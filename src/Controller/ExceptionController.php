<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\JsRoutingCommand;
use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Layout\Page;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Product;
use App\Entity\Security\User;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Model\Core\WebsiteModel;
use App\Model\Module\CatalogModel;
use App\Service\Content\MenuServiceInterface;
use App\Service\Content\SeoService;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ExceptionController.
 *
 * Manage render Exceptions
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ExceptionController extends BaseController
{
    protected int $statusCode = 0;
    protected bool $isDebug = false;
    protected ?WebsiteModel $website;

    /**
     * Page render.
     *
     * @throws NonUniqueResultException|InvalidArgumentException|ReflectionException|MappingException|QueryException
     */
    public function showAction(
        Request $request,
        MenuServiceInterface $menuService,
        SeoService $seoService,
        FlattenException|\Exception $exception,
        bool $isDebug,
        string $projectDir,
        ?DebugLoggerInterface $logger = null,
    ): Response {

        $this->isDebug = $isDebug;

        if (!$this->isDebug && $this->coreLocator->inAdmin() && !$this->getUser() instanceof User) {
            return $this->redirect($request->getSchemeAndHttpHost());
        }

        $this->statusCode = $exception->getStatusCode();
        $this->statusCode = 0 === $this->statusCode ? 500 : $this->statusCode;

        if (404 === $this->statusCode) {
            $website = $this->coreLocator->website();
            $configuration = $website->configuration;
            $inBuild = $this->coreLocator->redirectionService()->inBuild($request, $website, $configuration);
            if ($inBuild) {
                return $this->redirect($inBuild);
            }
        }

        $arguments = $this->setArguments($request, $exception, $menuService, $seoService, $logger);
        $template = $this->getTemplate($request, $projectDir);

        return $this->render($template, $arguments);
    }

    /**
     * Log javaScript errors.
     */
    #[Route('/core/dev/logger/javascript/errors', name: 'javascript_errors_logger', options: ['expose' => true, 'isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function jsErrorsLogger(Request $request, JsRoutingCommand $jsRoutingCommand, MailerInterface $mailer, string $logDir): JsonResponse
    {
        $routesErrors = 0;

        $logger = new Logger('javascript-critical-errors');
        $logger->pushHandler(new RotatingFileHandler($logDir.'/javascript-critical-errors.log', 20, Level::Critical));
        $message = 'JavaScript Error: ';
        foreach ($request->query->all() as $entitled => $value) {
            $value = urldecode($value);
            $message .= ucfirst($entitled).': '.$value.' ';
            if (str_contains($value, 'route') && str_contains($value, 'does not exist') || str_contains($value, 'fosjsrouting')) {
                ++$routesErrors;
            }
        }
        $logger->critical(trim($message));

        $send = true;
        $exceptions = ['chrome-extension'];
        foreach ($exceptions as $exception) {
            if (str_contains($message, $exception)) {
                $send = false;
                break;
            }
        }

        if ($send && !$this->isDebug) {
            try {
                $emails = ['dev@up-animations.fr'];
                foreach ($emails as $email) {
                    $notification = (new NotificationEmail())->from('dev@up-animations.fr')
                        ->to($email)
                        ->subject('Javascript ERROR')
                        ->markdown("<p>An error has occurred on website <a href='".$request->getSchemeAndHttpHost()."'>".$request->getSchemeAndHttpHost().'</a></p><br><p><small>'.trim($message).'</small></p>')
                        ->action('Aller sur le site', $request->getSchemeAndHttpHost())
                        ->importance(NotificationEmail::IMPORTANCE_URGENT);
                    $mailer->send($notification);
                }
            } catch (\Exception|TransportExceptionInterface $exception) {
            }
        }

        if ($routesErrors >= 2) {
            $jsRoutingCommand->dump();
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Get template.
     */
    private function getTemplate(Request $request, string $projectDir): string
    {
        $filesystem = new Filesystem();
        $dirname = $projectDir.'\templates\bundles\TwigBundle\Exception\\';
        $isNotFound = 404 === $this->statusCode;
        $isForbidden = 403 === $this->statusCode || 401 === $this->statusCode;
        $isDevEnv = $_ENV['APP_ENV'] === 'local' || $_ENV['APP_ENV'] === 'dev';
        $displayStackTraces = true === (bool)$_ENV['APP_DEBUG'] && $isDevEnv && !$isNotFound && !$isForbidden;
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);

        if ($displayStackTraces) {
            return '@Twig/Exception/stack-traces.html.twig';
        } elseif ($filesystem->exists($dirname.'error-'.$this->statusCode.'.html.twig')) {
            return '@Twig/Exception/error-'.$this->statusCode.'.html.twig';
        }

        return '@Twig/Exception/error.html.twig';
    }

    /**
     * Set page arguments.
     *
     * @throws NonUniqueResultException|InvalidArgumentException|ReflectionException|MappingException|QueryException
     */
    private function setArguments(
        Request $request,
        FlattenException|\Exception $exception,
        MenuServiceInterface $menuService,
        SeoService $seoService,
        ?DebugLoggerInterface $logger = null,
    ): array {

        $internalsIPS = ['::1', '127.0.0.1', 'fe80::1', '194.51.155.21', '195.135.16.88'];
        $allowedIP = $this->checkIP($internalsIPS);

        $arguments['is_debug'] = $this->isDebug;
        $arguments['logger'] = $logger;
        $arguments['status_code'] = $this->statusCode;
        $arguments['status_text'] = $exception->getMessage();
        $arguments['exception'] = $exception;
        $arguments['allowedIP'] = $allowedIP;
        $arguments['currentContent'] = null;
        $arguments['isFront'] = !$this->coreLocator->inAdmin();

        if ($this->coreLocator->inAdmin()) {
            if (!$request->attributes->get('website')) {
                $website = $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($request->getHost());
            } else {
                $website = $this->coreLocator->em()->getRepository(Website::class)->findObject(intval($request->attributes->get('website')));
            }
            $label = $this->coreLocator->translator()->trans('Tableau de bord', [], 'admin_breadcrumb');
            $dashboardArgs = $this->coreLocator->routeArgs('admin_dashboard');
            $arguments['breadcrumb'][$label] = $this->coreLocator->router()->generate('admin_dashboard', $dashboardArgs);
            $arguments['website'] = $this->website = $website;
            $arguments['configuration'] = $website->configuration;
            $arguments['template'] = 'admin';
        } else {

            $em = $this->coreLocator->em();
            $website = $this->getWebsite();
            $configuration = $website->configuration;
            $userBackIPS = $configuration ? $configuration->entity->getAllIPS() : [];
            $allowedIP = $this->checkIP($userBackIPS);
            $catalog = $em->getRepository(Catalog::class)->findOneBy(['website' => $website->entity, 'slug' => 'agencies']);
            $catalogModel = $catalog ? CatalogModel::fromEntity($catalog, $this->coreLocator, ['onlyForUrl' => true]) : null;

            $arguments['thumbConfigurationHeader'] = $this->thumbConfiguration($website, Block::class, 'block', null, 'title-header');
            $arguments['isUserBack'] = $allowedIP || $this->getUser() instanceof User;
            $arguments['website'] = $this->website = $website;
            $arguments['configuration'] = $configuration;
            $arguments['seo'] = $website->entity ? $this->getSeo($website->entity, $request, $seoService) : null;
            $arguments['template'] = $configuration->template;
            $arguments['templateName'] = 'error';
            $arguments['mainMenus'] = !$website->isEmpty ? $menuService->all($website) : [];
            $arguments['mainPages'] = $website->configuration->pages;
            $arguments['logos'] = $website->logos;
            $arguments['agencies'] = is_object($catalogModel) ? $catalogModel->products : [];
            $arguments['catalogMenus'] = $em->getRepository(Product::class)->findOnlineInMenus($website->entity, $this->coreLocator->locale());
        }

        return $arguments;
    }

    /**
     * Get SEO.
     *
     * @throws NonUniqueResultException|InvalidArgumentException|ReflectionException|MappingException|QueryException
     */
    private function getSeo(Website $website, Request $request, SeoService $seoService): bool|array
    {
        $em = $this->coreLocator->em();
        $defaultExceptionUrl = null;
        $currentLocaleExisting = false;
        $locales = $website->getConfiguration()->getAllLocales();
        $website = $em->getRepository(Website::class)->find($website->getId());
        $page = $em->getRepository(Page::class)->findOneBy([
            'website' => $website,
            'slug' => 'error',
        ]);

        $exceptionUrl = null;
        $requestLocale = $request->getLocale();

        if (!$page) {
            return false;
        }

        $urlsByLocale = [];
        foreach ($page->getUrls() as $url) {
            /* @var Url $url */
            $urlsByLocale[$url->getLocale()] = $url;
        }

        $createdBy = null;
        $needsFlush = false;

        foreach ($locales as $locale) {
            if ($locale !== $requestLocale) {
                continue;
            }

            $existingUrl = isset($urlsByLocale[$locale]);
            if ($existingUrl) {
                $currentLocaleExisting = true;
                $exceptionUrl = $urlsByLocale[$locale];
            }

            if (!$existingUrl || null === $exceptionUrl) {
                if (!$createdBy) {
                    /* @var User|null $createdBy */
                    $createdBy = $em->getRepository(User::class)->findOneBy(['login' => 'webmaster']);
                }

                $errorUrl = new Url();
                $errorUrl->setLocale($locale);
                $errorUrl->setCode('error');
                $errorUrl->setWebsite($website);
                $errorUrl->setHideInSitemap(true);
                $errorUrl->setAsIndex(false);
                $errorUrl->setCreatedBy($createdBy);
                $errorUrl->setOnline(true);

                $seo = new Seo();
                $seo->setUrl($errorUrl);
                $seo->setCreatedBy($createdBy);
                $errorUrl->setSeo($seo);

                $page->addUrl($errorUrl);
                $em->persist($page);
                $needsFlush = true;

                $exceptionUrl = $errorUrl;
                $currentLocaleExisting = true;

                if ($locale === $website->getConfiguration()->getLocale()) {
                    $defaultExceptionUrl = $errorUrl;
                }
            }
        }

        if ($needsFlush) {
            $em->flush();
        }

        if (!$currentLocaleExisting) {
            $exceptionUrl = $defaultExceptionUrl ?: null;
            $locale = $exceptionUrl ? $exceptionUrl->getLocale() : $website->getConfiguration()->getLocale();
            $request->setLocale($locale);
        }

        return $page && $exceptionUrl ? $seoService->execute($exceptionUrl, $page) : false;
    }

    /**
     * To check IP.
     */
    private function checkIP(array $IPS = []): bool
    {
        $clientIp = $this->coreLocator->requestStack()->getMainRequest()?->getClientIp();

        return null !== $clientIp && \Symfony\Component\HttpFoundation\IpUtils::checkIp($clientIp, $IPS);
    }
}
