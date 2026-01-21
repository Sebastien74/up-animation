<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Module\Timeline\Timeline;
use App\Repository\Module\Timeline\TimelineRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TimelineController.
 *
 * Front Timeline renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TimelineController extends FrontController
{
    /**
     * To display timeline.
     *
     * @throws NonUniqueResultException|\Exception
     */
    public function view(Request $request, TimelineRepository $timelineRepository, ?Block $block = null, mixed $filter = null): Response
    {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'timeline_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $website = $this->getWebsite();
        $timeline = $timelineRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;

        if (!$timeline) {
            return new Response();
        }

        $entity = $block instanceof Block ? $block : $timeline;
        $entity->setUpdatedAt($timeline->getUpdatedAt());

        $response = $this->render('front/'.$websiteTemplate.'/actions/timeline/view.html.twig', [
            'timeline' => $timeline,
            'thumbConfiguration' => $this->thumbConfiguration($website, Timeline::class, 'view', null),
            'configuration' => $configuration,
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'timeline_view', 'GENERATE', $response);
    }
}
