<?php

declare(strict_types=1);

namespace App\Controller\Front\Module;

use App\Controller\Front\FrontController;
use App\Model\Core\WebsiteModel;
use App\Repository\Core\WebsiteRepository;
use App\Repository\Gdpr\CategoryRepository;
use App\Repository\Gdpr\GroupRepository;
use App\Repository\Layout\PageRepository;
use App\Service\Core\GdprService;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * GdprController.
 *
 * Front Gdpr renders & management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/gdpr', schemes: '%protocol%')]
class GdprController extends FrontController
{
    /**
     * Modal View.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    #[Route('/html/render/modal.{_format}', name: 'front_gdpr_modal', requirements: ['_format' => 'json'], options: ['isMainRequest' => false, 'expose' => true], methods: 'GET', schemes: '%protocol%')]
    public function modal(Request $request, WebsiteRepository $websiteRepository, CategoryRepository $categoryRepository, PageRepository $pageRepository): JsonResponse
    {
        $website = $websiteRepository->findCurrent();
        $categories = $categoryRepository->findActiveByConfigurationAndLocale($website->configuration, $request->getLocale());
        $cookiesPage = $pageRepository->findCookiesPage($website, $request->getLocale());

        return new JsonResponse(['html' => $this->renderView('gdpr/modal.html.twig', [
            'website' => $website,
            'categories' => $categories,
            'cookiesPage' => $cookiesPage,
        ])]);
    }

    /**
     * Legacy View.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    #[Route('/html/render/legacy', name: 'front_gdpr_legacy', options: ['isMainRequest' => false, 'expose' => true], methods: 'GET', schemes: '%protocol%')]
    public function legacy(Request $request, WebsiteRepository $websiteRepository, CategoryRepository $categoryRepository): Response
    {
        $website = $websiteRepository->findCurrent();
        $categories = $categoryRepository->findActiveByConfigurationAndLocale($website->configuration, $request->getLocale());

        return $this->render('gdpr/legacy.html.twig', [
            'website' => $website,
            'categories' => $categories,
        ]);
    }

    /**
     * Get Cookies DB.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    #[Route('/html/cookies/db/{slug}/data.{_format}', name: 'front_gdpr_cookies_db', requirements: ['_format' => 'json'], options: ['isMainRequest' => false, 'expose' => true], methods: 'GET', schemes: '%protocol%')]
    public function cookiesDB(Request $request, WebsiteRepository $websiteRepository, GroupRepository $groupRepository, string $slug): JsonResponse
    {
        $website = $websiteRepository->findCurrent();
        $group = $groupRepository->findByConfiguration($website->configuration, $slug);
        $cookies = [];

        if ($group) {
            $asGoogle = str_contains($group->getSlug(), 'google');
            $asFacebook = str_contains($group->getSlug(), 'pixel');
            if ($asGoogle || $asFacebook) {
                $requestCookies = $request->cookies->all();
                foreach ($requestCookies as $cookieName => $cookieValue) {
                    if ($asGoogle && str_contains($cookieName, '_ga') || $asFacebook && '_fbp' === $cookieName) {
                        $cookies[] = $cookieName;
                    }
                }
            }
            foreach ($group->getGdprcookies() as $cookie) {
                $push = $asGoogle && in_array($cookie->getCode(), $cookies) || !$asGoogle;
                if ($push) {
                    $cookies[] = $cookie->getCode();
                }
            }
        }

        $response = new JsonResponse([
            'slug' => $group->getSlug(),
            'cookies' => array_unique($cookies),
        ]);

        foreach ($cookies as $name) {
            unset($_COOKIE[$name]);
            setcookie($name, '0', time() - 3600, '/', $request->getHost(), true);
            setcookie($name, '0', time() - 3600, '/', '.'.$request->getHost(), true);
        }

        return $response;
    }

    /**
     * Get Header scripts and html tags.
     *
     * @throws NonUniqueResultException|\Exception|InvalidArgumentException
     */
    #[Route('/html/render/scripts.{_format}', name: 'front_gdpr_scripts', requirements: ['_format' => 'json'], options: ['isMainRequest' => false, 'expose' => true], methods: 'GET', schemes: '%protocol%')]
    public function scripts(Request $request, WebsiteRepository $websiteRepository, CategoryRepository $categoryRepository, string $projectDir): JsonResponse
    {
        $reload = false;
        $website = $websiteRepository->findCurrent(true);
        $api = $website['api'];
        $cookiesRequest = $this->getCookies($request->cookies->get('felixCookies'));
        $cookies = !empty($cookiesRequest) ? $cookiesRequest : [];
        $cookiesCategories = $categoryRepository->findActiveByConfigurationAndLocale($website['configuration'], $request->getLocale());

        foreach ($cookies as $service => $status) {
            if (!$status) {
                $reload = true;
            }
        }

        $response = new JsonResponse([
            'headerScripts' => $this->getScripts($website, $api, 'scripts', $cookies, $cookiesCategories, $projectDir),
            'bodyPrependScripts' => $this->getScripts($website, $api, 'body-prepend', $cookies, $cookiesCategories, $projectDir),
            'bodyAppendScripts' => $this->getScripts($website, $api, 'body-append', $cookies, $cookiesCategories, $projectDir),
            'reloadModal' => $this->checkModalReload($cookiesCategories, $cookies),
            'haveCookies' => !empty($cookies),
            'cookies' => $request->cookies->get('felixCookies'),
            'reload' => $reload,
        ]);
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * Remove too old data.
     *
     * @throws \Exception
     */
    #[Route('/remove/old/data', name: 'front_gdpr_remove_data', options: ['isMainRequest' => false, 'expose' => true], methods: 'GET|DELETE', schemes: '%protocol%')]
    public function removeData(Request $request, GdprService $gdprService): JsonResponse
    {
        $gdprService->removeData($this->getWebsite()->entity);
        if ($request->get('referer')) {
            $session = new Session();
            $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans('Les données ont été supprimées avec succès.', [], 'admin'));
        }

        return new JsonResponse(['success' => true, 'reload' => true]);
    }

    /**
     * Get Scripts to inject in view.
     */
    private function getScripts(WebsiteModel $website, array $api, string $dirname, array $cookies, array $cookiesCategories, string $projectDir): string
    {
        $formData = !empty($_GET['gdprData']) ? (array) json_decode($_GET['gdprData']) : [];
        $fileSystem = new Filesystem();
        $scripts = '';

        foreach ($cookiesCategories as $category) {
            foreach ($category['gdprgroups'] as $group) {
                $slug = $group['slug'];
                $active = isset($cookies[$slug]) && $cookies[$slug]
                    || isset($cookies[$slug]) && !$cookies[$slug] && $group['anonymize']
                    || isset($formData[$slug]) && 'on' == $formData[$slug]
                    || !isset($cookies[$slug]) && $group['anonymize'];

                if ($active) {
                    $scriptTemplate = 'gdpr/'.$dirname.'/'.$slug.'.html.twig';
                    $scriptDirname = $projectDir.'/templates/'.$scriptTemplate;
                    $scriptDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $scriptDirname);
                    $status = isset($cookies[$slug]) && $cookies[$slug];

                    if ($fileSystem->exists($scriptDirname)) {
                        $scripts .= $this->renderView($scriptTemplate, [
                            'status' => $status,
                            'code' => $slug,
                            'website' => $website,
                            'api' => $api,
                        ]);
                    } elseif (!empty($group['script']) && $status) {
                        if (($group['scriptInHead'] && 'scripts' === $dirname) || (!$group['scriptInHead'] && 'body-prepend' === $dirname)) {
                            $scripts .= $group['script'];
                        }
                    }
                }
            }
        }

        return $scripts;
    }

    /**
     * Get Cookies.
     */
    private function getCookies($cookiesRequest): array
    {
        $cookies = [];
        $serializer = new Serializer([new GetSetMethodNormalizer()], ['json' => new JsonEncoder()]);
        if (!empty($cookiesRequest)) {
            $cookiesRequest = $serializer->decode($cookiesRequest, 'json');
            foreach ($cookiesRequest as $cookie) {
                $cookies[$cookie['slug']] = $cookie['status'];
            }
        }

        return $cookies;
    }

    /**
     * To check if groups is different between DB and User Cookies.
     */
    private function checkModalReload(array $cookiesCategories, array $cookies = []): bool
    {
        $activesGroups = [];
        foreach ($cookiesCategories as $category) {
            foreach ($category['gdprgroups'] as $group) {
                if ($group['active'] && 'functional' !== $category['slug']) {
                    $activesGroups[] = $group['slug'];
                }
            }
        }
        sort($activesGroups);
        $activesGroups = json_encode($activesGroups);

        $cookiesGroups = [];
        foreach ($cookies as $slug => $cookie) {
            $cookiesGroups[] = $slug;
        }
        sort($cookiesGroups);
        $cookiesGroups = json_encode($cookiesGroups);

        return $activesGroups !== $cookiesGroups;
    }
}
