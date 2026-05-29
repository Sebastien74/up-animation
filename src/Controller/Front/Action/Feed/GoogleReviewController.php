<?php

declare(strict_types=1);

namespace App\Controller\Front\Action\Feed;

use App\Controller\Front\ActionController;
use App\Service\Content\ActionService;
use App\Service\Content\Feed\GoogleReviewService;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\FrontLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;

/**
 * GoogleReviewController.
 *
 * Front Google reviews renders.
 */
class GoogleReviewController extends ActionController
{
    public function __construct(
        private readonly GoogleReviewService $googleReviewService,
        #[AutowireLocator(ActionService::class, indexAttribute: 'key')] ServiceLocator $actionLocator,
        FrontLocatorInterface $frontLocator,
        CoreLocatorInterface $coreLocator
    ) {
        parent::__construct($actionLocator, $frontLocator, $coreLocator);
    }

    /**
     * Render Google reviews.
     */
    public function index(): Response
    {
        $website = $this->getWebsite();
        $googleModel = $website->api?->google;

        if (!$googleModel || !$googleModel->mapKey || !$googleModel->placeId) {
            return new Response();
        }

        $data = $this->googleReviewService->getReviews($googleModel);
        
        $template = $website->configuration->template;

        return $this->render('front/' . $template . '/actions/feed/google/reviews.html.twig', [
            'google' => $googleModel,
            'reviews' => $data['reviews'] ?? [],
            'rating' => $data['rating'] ?? null,
            'user_ratings_total' => $data['user_ratings_total'] ?? null,
        ]);
    }
}
