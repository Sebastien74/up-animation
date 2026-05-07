<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Model\Core\ConfigurationModel;
use App\Model\Core\WebsiteModel;
use App\Model\ViewModel;
use DateTimeInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * IndexController.
 *
 * Front index controller to manage main pages
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IndexController extends FrontController
{
    /**
     * To logout user.
     *
     * @throws Exception
     */
    #[Route('/logout', name: 'app_logout', methods: 'GET', schemes: '%protocol%', priority: 1000)]
    public function logout(): void
    {
        /* controller can be blank: it will never be executed! */
        throw new Exception("Don't forget to activate logout in security.yaml");
    }

    /**
     * Page view.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|\ReflectionException|QueryException|MappingException
     */
    #[Route('/{url}', name: 'front_index', defaults: ['url' => null], methods: 'GET|POST', schemes: '%protocol%', priority: 500)]
    #[Route([
        'fr' => '/mon-espace-personnel/{url}',
        'en' => '/my-personal-space/{url}',
        'es' => '/mi-espacio-personal/{url}',
        'it' => '/mio-spazio-personale/{url}',
    ], name: 'front_index_security', methods: 'GET', schemes: '%protocol%')]
    #[Cache(expires: 'tomorrow', public: true)]
    public function view(
        Request $request,
        ?string $url = null,
        bool $preview = false
    ): RedirectResponse|Response {

        $website = $this->getWebsite();
        if ($website->isEmpty) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Site non configuré !!", [], 'front'));
        }

        $response = new Response();

        /* Optimization: HTTP Cache validation before full page hydration */
        if ((!$preview && !$this->coreLocator->isDebug()) || self::FORCE_CACHE) {
            $stamp = $this->coreLocator->em()->getRepository(Page::class)
                ->findCacheStampByUrlAndLocale($website, $url, $request->getLocale(), $preview);
            if (is_array($stamp) && empty($stamp['pageSecure'])) {
                $lastUpdate = $this->resolveStampLastUpdate($website, $stamp);
                $response->setLastModified($lastUpdate);
                $response->setEtag(md5($stamp['urlId'].$lastUpdate->getTimestamp().$request->getLocale()));
                $response->setPublic();
                if ($response->isNotModified($request)) {
                    return $response;
                }
            }
        }

        $page = $this->getPage($website, $request, $preview, $url);

        $requestUri = $request->getRequestUri();
        $pageSlug = $page instanceof Page ? $page->getSlug() : null;

        /* 404 & Redirection */
        if (!$page instanceof Page || 'components' === $pageSlug && !$this->isGranted('ROLE_INTERNAL')) {
            if ('components' === $pageSlug) {
                $session = $request->hasSession() ? $request->getSession() : $this->coreLocator->requestStack()->getSession();
                $session->getFlashBag()->add('info', 'Veuillez vous connecter pour visualiser cette page.');
                $session->set('alert_error', true);
            } elseif (is_array($page) && !empty($page['redirection'])) {
                return $this->redirectToRoute('front_index', ['url' => $page['redirection']], 301);
            }
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Cette page n'existe pas !!", [], 'front'));
        }

        $urlEntity = $page->getUrls()->first();
        if (!$urlEntity instanceof Url) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Cette page n'a pas d'URL !!", [], 'front'));
        }

        if (!$preview && $page->isAsIndex() && !empty($requestUri) && '/' != $requestUri && !preg_match('/\?*=/', $requestUri)) {
            return $this->redirectToRoute('front_index', [], 301);
        }

        /* To redirect if pagination == 1 */
        if ($request->query->get('page') && 1 == $request->query->get('page') && !str_contains($request->getUri(), 'ajax')) {
            $query = $request->query->all();
            unset($query['page']);
            $redirectUrl = $request->getPathInfo();
            if (!empty($query)) {
                $redirectUrl .= '?' . http_build_query($query);
            }
            return $this->redirect($redirectUrl);
        }

        /* To redirect the build page if the website is online */
        if (!$preview && 'build.html.twig' === $page->getTemplate() && $website->configuration->onlineStatus) {
            return $this->redirectToRoute('front_index');
        }

        /* Secure page redirection */
        if ($page->isSecure()) {
            $userAllowed = $this->isGranted('ROLE_USER_FRONT') || ($this->isGranted('ROLE_SECURE_PAGE') && $this->isGranted('IS_IMPERSONATOR'));
            if (!$userAllowed) {
                return $this->redirectToRoute('app_logout');
            } elseif ('front_index_security' !== $request->attributes->get('_route') && $this->isGranted('ROLE_USER_FRONT')) {
                return $this->redirectToRoute('front_index_security', ['url' => $urlEntity->getCode()], 301);
            }
        }

        /* Set request */
        $request->setLocale($urlEntity->getLocale());

        return $this->render(
            $this->getTemplate($website->configuration, $page),
            $this->getArguments($website, $page, $urlEntity),
            $response
        );
    }

    /**
     * Resolve last-update date from a scalar stamp projection.
     */
    private function resolveStampLastUpdate(WebsiteModel $website, array $stamp): DateTimeInterface
    {
        $lastUpdate = $stamp['pageCreatedAt'] instanceof DateTimeInterface ? $stamp['pageCreatedAt'] : new \DateTimeImmutable();

        foreach ([$stamp['pageUpdatedAt'] ?? null, $stamp['urlUpdatedAt'] ?? null, $website->entity->getCacheClearDate()] as $date) {
            if ($date instanceof DateTimeInterface && $date > $lastUpdate) {
                $lastUpdate = $date;
            }
        }

        return $lastUpdate;
    }

    /**
     * Preview.
     */
    #[Route('/admin-%security_token%/{website}/front/page/preview/{url}', name: 'front_page_preview', methods: 'GET|POST', schemes: '%protocol%')]
    #[IsGranted('ROLE_ADMIN')]
    public function preview(Request $request, Website $website, Url $url): Response
    {
        $request->setLocale($url->getLocale());

        return $this->forward('App\Controller\Front\IndexController::view', [
            'url' => $url->getCode(),
            'website' => $website,
            'preview' => true,
        ]);
    }

    /**
     * Get the current Page.
     */
    private function getPage(WebsiteModel $website, Request $request, bool $preview, ?string $url = null): Page|array|null
    {
        $pageRepository = $this->coreLocator->em()->getRepository(Page::class);

        return !$url ? $pageRepository->findIndex($website, $request->getLocale(), $preview)
            : $pageRepository->findByUrlCodeAndLocale($website, $url, $request->getLocale(), $preview);
    }

    private static array $templatesCache = [];

    /**
     * Get Page template.
     */
    private function getTemplate(ConfigurationModel $configuration, Page $page): string
    {
        $template = 'components' === $page->getSlug() ? 'components.html.twig' : $page->getTemplate();
        $templateDir = 'front/'.$configuration->template.'/template/'.$template;
        $cacheKey = $templateDir;

        if (isset(self::$templatesCache[$cacheKey])) {
            return self::$templatesCache[$cacheKey];
        }

        $fileSystem = new Filesystem();
        $projectDir = $this->coreLocator->projectDir();
        $fullPath = $projectDir.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $templateDir);

        if ($fileSystem->exists($fullPath)) {
            return self::$templatesCache[$cacheKey] = str_replace(['\\'], '/', $templateDir);
        }

        return self::$templatesCache[$cacheKey] = str_replace(['\\'], '/', str_replace($template, 'cms.html.twig', $templateDir));
    }

    /**
     * Get Page arguments.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|\ReflectionException|QueryException
     */
    private function getArguments(WebsiteModel $website, Page $page, Url $url): array
    {
        $cacheKey = 'page_args_' . $page->getId() . '_' . $url->getLocale();
        if (isset(self::$argsCache[$cacheKey])) {
            return self::$argsCache[$cacheKey];
        }

        $pageModel = ViewModel::fromEntity($page, $this->coreLocator, ['disabledMedias' => false, 'disabledIntl' => false]);
        $seo = $this->coreLocator->seoService()->execute($url, $pageModel, null, false, $website);
        $interface = !empty($seo['interface']) ? $seo['interface'] : $this->getInterface(Page::class);

        return self::$argsCache[$cacheKey] = array_merge([
            'seo' => $seo,
            'templateName' => str_contains($this->coreLocator->request()->attributes->get('_route'), '_security') ? 'security' : str_replace('.html.twig', '', $pageModel->template),
            'interface' => $interface,
            'interfaceName' => !empty($interface['name']) ? $interface['name'] : null,
            'thumbConfiguration' => $this->thumbConfiguration($website, Page::class),
            'entityModel' => $pageModel,
            'intlMedia' => $pageModel->mainMedia,
            'entity' => $pageModel->entity,
        ], $this->defaultArgs($website, $url, $pageModel));
    }

    private static array $argsCache = [];
}
