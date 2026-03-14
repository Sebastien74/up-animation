<?php

declare(strict_types=1);

namespace App\Controller\Front\Action\Feed;

use App\Controller\Front\ActionController;
use App\Service\Content\ActionService;
use App\Service\Content\YouTubeService;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\FrontLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Response;

/**
 * YouTubeController.
 *
 * Front YouTube feed renders.
 */
class YouTubeController extends ActionController
{
    public function __construct(
        private readonly YouTubeService $youtubeService,
        #[AutowireLocator(ActionService::class, indexAttribute: 'key')] ServiceLocator $actionLocator,
        FrontLocatorInterface $frontLocator,
        CoreLocatorInterface $coreLocator
    ) {
        parent::__construct($actionLocator, $frontLocator, $coreLocator);
    }

    /**
     * Render YouTube feed.
     */
    public function index(): Response
    {
        $website = $this->getWebsite();
        $googleModel = $website->api?->google;

        if (!$googleModel || !$googleModel->youtubeApiKey || !$googleModel->youtubeChannelId) {
            return new Response();
        }

        $videos = $this->youtubeService->getVideos($googleModel);
        
        $template = $website->configuration->template;

        return $this->render('front/' . $template . '/actions/feed/youtube/html.twig', [
            'google' => $googleModel,
            'videos' => $videos,
        ]);
    }
}
