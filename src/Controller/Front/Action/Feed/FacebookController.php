<?php

declare(strict_types=1);

namespace App\Controller\Front\Action\Feed;

use App\Controller\Front\ActionController;
use App\Service\Content\ActionService;
use App\Service\Content\FacebookService;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\FrontLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;

/**
 * FacebookController.
 *
 * Front Facebook feed renders.
 */
class FacebookController extends ActionController
{
    public function __construct(
        private readonly FacebookService $facebookService,
        #[AutowireLocator(ActionService::class, indexAttribute: 'key')] ServiceLocator $actionLocator,
        FrontLocatorInterface $frontLocator,
        CoreLocatorInterface $coreLocator
    ) {
        parent::__construct($actionLocator, $frontLocator, $coreLocator);
    }

    /**
     * Render Facebook feed.
     */
    public function index(): Response
    {
        $website = $this->getWebsite();
        $facebookModel = $website->api?->facebook;

        if (!$facebookModel || !$facebookModel->accessToken || !$facebookModel->pageId) {
            return new Response();
        }

        $feed = $this->facebookService->getFeed($facebookModel);
        
        $template = $website->configuration->template;

        return $this->render('front/' . $template . '/actions/feed/facebook/html.twig', [
            'facebook' => $facebookModel,
            'feed' => $feed,
        ]);
    }
}
