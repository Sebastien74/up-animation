<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Model\Core\ConfigurationModel;
use App\Model\Core\WebsiteModel;
use App\Model\ViewModel;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
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
     * @throws \Exception
     */
    #[Route('/logout', name: 'app_logout', methods: 'GET', schemes: '%protocol%', priority: 1000)]
    public function logout(): void
    {
        /* controller can be blank: it will never be executed! */
        throw new \Exception("Don't forget to activate logout in security.yaml");
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
        $page = !$website->isEmpty ? $this->getPage($website, $request, $preview, $url) : [];
        $requestUri = $request->getRequestUri();
        $pageSlug = $page instanceof Page ? $page->getSlug() : null;

        /* 404 & Redirection */
        if (!$page instanceof Page || 'components' === $pageSlug && !$this->isGranted('ROLE_INTERNAL')) {
            if ('components' === $pageSlug) {
                $session = new Session();
                $session->getFlashBag()->add('info', 'Veuillez vous connecter pour visualiser cette page.');
                $session->set('alert_error', true);
            } elseif (is_array($page) && !empty($page['redirection'])) {
                return $this->redirectToRoute('front_index', ['url' => $page['redirection']], 301);
            }
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Cette page n'existe pas !!", [], 'front'));
        }

        $url = $page->getUrls()->first();
        if (!$preview && $page->isAsIndex() && !empty($requestUri) && '/' != $requestUri && !preg_match('/\?*=/', $requestUri)) {
            return $this->redirectToRoute('front_index', [], 301);
        }

        /* To redirect if pagination == 1 */
        $mainRequest = $this->coreLocator->request();
        if ($mainRequest->get('page') && 1 == $mainRequest->get('page') && !str_contains($mainRequest->getUri(), 'ajax')) {
            $query = $mainRequest->query->all();
            unset($query['page']);
            $url = $mainRequest->getPathInfo();
            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }
            return $this->redirect($url);
        }

        /* To redirect build page if website is online */
        if (!$preview && 'build.html.twig' === $page->getTemplate() && $website->configuration->onlineStatus) {
            return $this->redirectToRoute('front_index');
        }

        /* Secure page redirection */
        if ($page->isSecure()) {
            $userAllowed = $this->isGranted('ROLE_USER_FRONT') || ($this->isGranted('ROLE_SECURE_PAGE') && $this->isGranted('IS_IMPERSONATOR'));
            if (!$userAllowed) {
                return $this->redirectToRoute('app_logout');
            } elseif ('front_index_security' !== $request->get('_route') && $this->isGranted('ROLE_USER_FRONT')) {
                return $this->redirectToRoute('front_index_security', ['url' => $url->getCode()], 301);
            }
        }

        /** To display cache pool */
        if (self::CACHE_POOL) {
            $poolResponse = $this->cachePool($page, 'page', 'GET');
            if ($poolResponse && !$page->isSecure()) {
                return $poolResponse;
            }
        }

        /* Set request */
        $request->setLocale($url->getLocale());
        $response = $this->render($this->getTemplate($website->configuration, $page), $this->getArguments($website, $page, $url));

        return $this->cachePool($page, 'page', 'GENERATE', $response);
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
            'preview' => true,
        ]);
    }

    /**
     * Get current Page.
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
        $pageModel = ViewModel::fromEntity($page, $this->coreLocator, ['disabledMedias' => false, 'disabledIntl' => false]);
        $seo = $this->coreLocator->seoService()->execute($url, $pageModel, null, false, $website);
        $interface = !empty($seo['interface']) ? $seo['interface'] : $this->getInterface(Page::class);

        return array_merge([
            'seo' => $seo,
            'templateName' => str_contains($this->coreLocator->request()->get('_route'), '_security') ? 'security' : str_replace('.html.twig', '', $pageModel->template),
            'interface' => $interface,
            'interfaceName' => !empty($interface['name']) ? $interface['name'] : null,
            'thumbConfiguration' => $this->thumbConfiguration($website, Page::class),
            'entityModel' => $pageModel,
            'intlMedia' => $pageModel->mainMedia,
            'entity' => $pageModel->entity,
        ], $this->defaultArgs($website, $url, $pageModel));
    }
}
