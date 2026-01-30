<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\ActionController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

/**
 * SearchController.
 *
 * To render view like Newscast or Product.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ViewController extends ActionController
{
//    /**
//     * View.
//     */
//    #[Route([
//        'fr' => '/{pageUrl}/{url}',
//        'en' => '/{pageUrl}/{url}',
//    ], name: 'front_newscast_view', methods: 'GET', schemes: '%protocol%', priority: 300)]
//    #[Route([
//        'fr' => '/{pageUrl}/{url}',
//        'fr_ch' => '/{pageUrl}/{url}',
//        'en' => '/{pageUrl}/{url}',
//    ], name: 'front_catalogproduct_view', methods: 'GET', schemes: '%protocol%', priority: 300)]
//    #[Cache(expires: 'tomorrow', public: true)]
//    public function view(Request $request, string $url, ?string $pageUrl = null, bool $preview = false): Response|JsonResponse
//    {
//        /** @var string|null $route */
//        $route = $request->attributes->get('_route');
//
//        return match ($route) {
//            'front_newscast_view' => $this->forward(
//                'App\Controller\Front\Action\NewscastController::view',
//                [
//                    'request' => $request,
//                    'url' => $url,
//                    'pageUrl' => $pageUrl,
//                    'preview' => $preview,
//                ]
//            ),
//            'front_catalogproduct_view' => $this->forward(
//                'App\Controller\Front\Action\CatalogController::view',
//                [
//                    'request' => $request,
//                    'url' => $url,
//                    'pageUrl' => $pageUrl,
//                    'preview' => $preview,
//                ]
//            ),
//            default => throw $this->createNotFoundException('Cette page n’existe pas.'),
//        };
//    }
}