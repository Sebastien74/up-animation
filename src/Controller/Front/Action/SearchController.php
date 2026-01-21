<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Module\Search\Search;
use App\Entity\Seo\Url;
use App\Form\Manager\Front\SearchManager;
use App\Form\Type\Module\Search\FrontType;
use App\Repository\Layout\PageRepository;
use App\Repository\Module\Search\SearchRepository;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * SearchController.
 *
 * Front Search renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SearchController extends FrontController
{
    /**
     * View.
     *
     * @throws NonUniqueResultException
     */
    #[Route('/front/search/view/{filter}',
        name: 'front_search_view',
        options: ['isMainRequest' => false],
        methods: 'GET',
        schemes: '%protocol%')]
    public function view(
        Request $request,
        RequestStack $requestStack,
        SearchRepository $searchRepository,
        PageRepository $pageRepository,
        mixed $filter = null,
    ): Response {
        if (!$filter) {
            return new Response();
        }

        $block = $request->get('block') instanceof Block ? $request->get('block') : null;
        $cache = $this->coreLocator->cacheService()->cachePool($block, 'search_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $website = $this->getWebsite();
        $search = $searchRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);

        if (!$search) {
            return new Response();
        }

        $modal = $request->get('modal') && $search->isModal();
        $button = $request->get('button');
        $displayForm = $search->isModal() && !$button || !$search->isModal() && $button;

        $configuration = $website->configuration;
        $template = $configuration->template;
        $resultsPageUrl = $pageRepository->findOneUrlByPageAndLocale($request->getLocale(), $search->getResultsPage());
        $urlCode = $resultsPageUrl instanceof Url ? $resultsPageUrl->getCode() : null;

        $searchText = $requestStack->getMainRequest()->get('search');
        $searchText = $searchText ? urldecode($searchText) : $searchText;
        // XSS protection: reset the value if it contains potentially malicious content
        if (!is_string($searchText) || !preg_match('/^[\p{L}\p{N} _\-.,\'"]+$/u', $searchText)) {
            $searchText = null;
        }
        $form = $this->createForm(FrontType::class, null, [
            'field_data' => $searchText,
            'action' => $this->generateUrl('front_index', ['url' => $urlCode]),
            'method' => 'GET',
        ]);

        $response = $this->render('front/'.$template.'/actions/search/view.html.twig', [
            'resultsPage' => $resultsPageUrl,
            'search' => $search,
            'websiteTemplate' => $template,
            'form' => $displayForm ? $form->createView() : null,
            'website' => $website,
            'modal' => $modal,
            'btn' => $button,
            'scrollInfinite' => $search->isScrollInfinite(),
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'search_view', 'GENERATE', $response);
    }

    /**
     * Results view.
     *
     * @throws \Exception|InvalidArgumentException|NonUniqueResultException
     */
    #[Route('/front/search/results/{filter}', name: 'front_search_results', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function results(
        Request $request,
        RequestStack $requestStack,
        SearchManager $searchManager,
        SearchRepository $searchRepository,
        PageRepository $pageRepository,
        mixed $filter = null,
    ): Response {
        if (!$filter) {
            return new Response();
        }

        $website = $this->getWebsite();
        $search = $searchRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);
        $displayForm = 'only-results' !== $request->get('slug');

        if (!$search) {
            return new Response();
        }

        $searchText = $requestStack->getMainRequest()->get('search');
        $searchText = $searchText ? urldecode($searchText) : $searchText;
        // XSS protection: reset the value if it contains potentially malicious content
        if (!is_string($searchText) || !preg_match('/^[\p{L}\p{N} _\-.,\'"]+$/u', $searchText)) {
            $searchText = null;
        }
        $resultsPageUrl = $pageRepository->findOneUrlByPageAndLocale($request->getLocale(), $search->getResultsPage());
        $urlCode = $resultsPageUrl instanceof Url ? $resultsPageUrl->getCode() : null;
        $form = $displayForm ? $this->createForm(FrontType::class, null, [
            'field_data' => $searchText,
            'action' => $this->generateUrl('front_index', ['url' => $urlCode]),
            'method' => 'GET',
        ]) : null;

        $results = $searchText ? $searchManager->execute($search, $website->entity, $searchText) : [];
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        $currentPage = $requestStack->getMainRequest()->get('page') ? $requestStack->getMainRequest()->get('page') : 1;
        $allResults = $results['results'] ?? [];
        $counts = $results['counts'] ?? 0;
        $count = is_array($counts) && isset($counts['all']) ? $counts['all'] : 0;
        $template = 'front/'.$websiteTemplate.'/actions/search/results.html.twig';
        $arguments = [
            'searchText' => $searchText,
            'resultsPageUrl' => $resultsPageUrl,
            'search' => $search,
            'currentPage' => $currentPage,
            'websiteTemplate' => $websiteTemplate,
            'thumbConfiguration' => $this->thumbConfiguration($website, Search::class, 'index'),
            'filter' => $filter,
            'pagination' => !empty($results['pagination']) ? $results['pagination'] : [],
            'form' => $displayForm ? $form->createView() : null,
            'website' => $website,
            'results' => !empty($allResults[$currentPage]) ? $allResults[$currentPage] : [],
            'allResults' => $allResults,
            'counts' => $counts,
            'maxPage' => $count > 0 && $search->getItemsPerPage() > 0 ? intval(ceil($count / $search->getItemsPerPage())) : $count,
            'scrollInfinite' => $search->isScrollInfinite(),
        ];

        return $request->query->get('scroll-ajax') ? $this->cache($request, $template, $search, $arguments) : $this->render($template, $arguments);
    }
}
