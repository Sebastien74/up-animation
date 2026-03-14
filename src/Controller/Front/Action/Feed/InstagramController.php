<?php

declare(strict_types=1);

namespace App\Controller\Front\Action\Feed;

use App\Controller\Front\ActionController;
use App\Service\Content\ActionService;
use App\Service\Content\InstagramService;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\FrontLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;

/**
 * InstagramController.
 *
 * Front Instagram feed renders.
 */
class InstagramController extends ActionController
{
    public function __construct(
        private readonly InstagramService $instagramService,
        #[AutowireLocator(ActionService::class, indexAttribute: 'key')] ServiceLocator $actionLocator,
        FrontLocatorInterface $frontLocator,
        CoreLocatorInterface $coreLocator
    ) {
        parent::__construct($actionLocator, $frontLocator, $coreLocator);
    }

    /**
     * Render Instagram feed.
     */
    public function index(): Response
    {
        $website = $this->getWebsite();
        $instagramModel = $website->api?->instagram;

        if (!$instagramModel || !$instagramModel->accessToken) {
            return new Response();
        }

        $feed = $this->instagramService->getFeed($instagramModel);
        
        $template = $website->configuration->template;

        return $this->render('front/' . $template . '/actions/feed/instagram/html.twig', [
            'instagram' => $instagramModel,
            'feed' => $feed,
        ]);
    }
}
