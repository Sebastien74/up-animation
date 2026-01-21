<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Module\Agenda\Agenda;
use App\Repository\Module\Agenda\AgendaRepository;
use App\Repository\Module\Agenda\PeriodRepository;
use App\Service\Content\AgendaService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * AgendaController.
 *
 * Front Agenda renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AgendaController extends FrontController
{
    /**
     * To display agenda.
     *
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function view(
        Request $request,
        AgendaService $agendaService,
        AgendaRepository $agendaRepository,
        ?Block $block = null,
        mixed $filter = null,
    ): Response {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'agenda_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $website = $this->getWebsite();
        $agenda = $agendaRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;

        if (!$agenda) {
            return new Response();
        }

        $entity = $block instanceof Block ? $block : $agenda;
        $entity->setUpdatedAt($agenda->getUpdatedAt());

        $response = $this->render('front/'.$websiteTemplate.'/actions/agenda/view.html.twig', array_merge($agendaService->eventsDaysData($agenda), [
            'periodDate' => new \DateTime('now', new \DateTimeZone('Europe/Paris')),
            'agenda' => $agenda,
            'configuration' => $configuration,
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
        ]));

        return $this->coreLocator->cacheService()->cachePool($block, 'agenda_view', 'GENERATE', $response);
    }

    /**
     * Get Period.
     *
     * @throws \Exception
     */
    #[Route('/front/agenda/period/{agenda}/{date}', name: 'front_agenda_period', options: ['isMainRequest' => false, 'expose' => true], methods: 'GET', schemes: '%protocol%')]
    public function period(
        Request $request,
        PeriodRepository $periodRepository,
        AgendaService $agendaService,
        Agenda $agenda,
        string $date): JsonResponse
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        $period = $request->get('period') ? $periodRepository->find($request->get('period')) : null;

        return new JsonResponse([
            'success' => true,
            'html' => $this->renderView('front/'.$websiteTemplate.'/actions/agenda/view.html.twig', array_merge($agendaService->eventsDaysData($agenda), [
                'periodDate' => new \DateTime($date),
                'agenda' => $agenda,
                'period' => $period,
                'website' => $website,
                'websiteTemplate' => $websiteTemplate,
            ]))]);
    }
}
