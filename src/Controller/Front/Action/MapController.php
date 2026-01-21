<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Model\Module\MapModel;
use App\Repository\Module\Map\MapRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * MapController.
 *
 * Front Map renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MapController extends FrontController
{
    /**
     * View.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    #[Route('/front/map/view/{filter}', name: 'front_map_view', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function view(Request $request, MapRepository $mapRepository, ?Block $block = null, mixed $filter = null): Response
    {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'map_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $website = $this->getWebsite();
        $map = $mapRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);

        if (!$map) {
            return new Response();
        }

        $configuration = $website->configuration;
        $template = $configuration->template;
        $entity = $block instanceof Block ? $block : $map;
        $entity->setUpdatedAt($map->getUpdatedAt());
        $mapModel = MapModel::fromEntity($map, $this->coreLocator);

        $response = $this->render('front/'.$template.'/actions/map/view.html.twig', [
            'websiteTemplate' => $template,
            'website' => $website,
            'map' => $mapModel->entity,
            'categories' => $mapModel->categories,
            'points' => $mapModel->points,
            'block' => $block,
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'map_view', 'GENERATE', $response);
    }
}
