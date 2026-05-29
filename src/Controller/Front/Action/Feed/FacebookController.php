<?php

declare(strict_types=1);

namespace App\Controller\Front\Action\Feed;

use App\Controller\Front\ActionController;
use App\Entity\Api\FeedPost;
use App\Repository\Api\FeedPostRepository;
use App\Service\Content\ActionService;
use App\Service\Content\Feed\FeedAutoSyncService;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\FrontLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;

/**
 * FacebookController.
 *
 * Renders the Facebook feed from the locally-persisted FeedPost entries.
 * The live Facebook API is only hit by the app:feed:sync command.
 */
class FacebookController extends ActionController
{
    public function __construct(
        private readonly FeedPostRepository $feedPostRepository,
        private readonly FeedAutoSyncService $feedAutoSyncService,
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
        $limit = $facebookModel?->nbrItems ?: 10;

        if ($facebookModel?->accessToken && $facebookModel?->pageId) {
            $this->feedAutoSyncService->scheduleIfStale(FeedPost::PROVIDER_FACEBOOK);
        }

        $posts = $this->feedPostRepository->findActiveByProvider(FeedPost::PROVIDER_FACEBOOK, $limit);
        if ($posts === []) {
            return new Response();
        }

        $template = $website->configuration->template;

        return $this->render('front/' . $template . '/actions/feed/facebook/html.twig', [
            'facebook' => $facebookModel,
            'feed' => $posts,
        ]);
    }
}
