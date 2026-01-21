<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Module\Tab\Content;
use App\Model\Module\TabModel;
use App\Repository\Module\Tab\TabRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TabController.
 *
 * Front Tab render
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TabController extends FrontController
{
    /**
     * View.
     *
     * @throws NonUniqueResultException|\Exception
     */
    #[Route('/front/tab/view/{filter}', name: 'front_tab_view', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function view(Request $request, TabRepository $tabRepository, ?Block $block = null, mixed $filter = null): Response
    {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'tab_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $website = $this->getWebsite();
        $tab = $tabRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);

        if (!$tab) {
            return new Response();
        }

        $configuration = $website->configuration;
        $template = $configuration->template;
        $entity = $block instanceof Block ? $block : $tab;
        $entity->setUpdatedAt($tab->getUpdatedAt());
        $tab = TabModel::fromEntity($tab, $this->coreLocator);

        $response = $this->render('front/'.$template.'/actions/tab/view.html.twig', [
            'tab' => $tab,
            'website' => $website,
            'websiteTemplate' => $template,
            'thumbConfiguration' => $this->thumbConfiguration($website, Content::class, 'view'),
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'tab_view', 'GENERATE', $response);
    }
}
