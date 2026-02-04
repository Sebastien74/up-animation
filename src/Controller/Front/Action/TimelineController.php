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
        $website = $this->getWebsite();
        $timeline = $filter ? $timelineRepository->findOneByFilter($website->entity, $request->getLocale(), $filter) : false;

        if (!$timeline) {
            return new Response();
        }

        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;

        $entity = $block instanceof Block ? $block : $timeline;
        $entity->setUpdatedAt($timeline->getUpdatedAt());

        return $this->render('front/'.$websiteTemplate.'/actions/timeline/view.html.twig', [
            'timeline' => $timeline,
            'thumbConfiguration' => $this->thumbConfiguration($website, Timeline::class, 'view', null),
            'configuration' => $configuration,
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
        ]);
    }
}
