<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Model\Module\TableModel;
use App\Repository\Module\Table\TableRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TableController.
 *
 * Front Table renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TableController extends FrontController
{
    /**
     * View.
     *
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    #[Route('/front/table/view/{filter}', name: 'front_table_view', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function view(
        Request $request,
        TableRepository $tableRepository,
        ?Block $block = null,
        mixed $filter = null,
    ): Response {

        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'table_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $website = $this->getWebsite();
        $table = $tableRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);

        if (!$table) {
            return new Response();
        }

        $configuration = $website->configuration;
        $template = $configuration->template;
        $entity = $block instanceof Block ? $block : $table;
        $table = TableModel::fromEntity($table, $this->coreLocator);
        $entity->setUpdatedAt($table->entity->getUpdatedAt());

        $response = $this->render('front/'.$template.'/actions/table/view.html.twig', [
            'websiteTemplate' => $template,
            'website' => $website,
            'table' => $table,
            'render' => $table->render,
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'table_view', 'GENERATE', $response);
    }
}
