<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Agenda;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Agenda\Agenda;
use App\Entity\Module\Agenda\Period;
use App\Form\Type\Module\Agenda\PeriodType;
use App\Service\Content\AgendaService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AgendaController.
 *
 * Period Agenda Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_AGENDA')]
#[Route('/admin-%security_token%/{website}/agendas/periods', schemes: '%protocol%')]
class PeriodController extends AdminController
{
    protected ?string $class = Period::class;
    protected ?string $formType = PeriodType::class;

    /**
     * New Period.
     *
     * @throws \Exception|\Psr\Cache\InvalidArgumentException
     */
    #[Route('/{agenda}/new/{date}', name: 'admin_agendaperiod_new', options: ['expose' => true], methods: 'GET|POST')]
    public function new(Request $request)
    {
        $website = $this->getWebsite();
        $agenda = $this->coreLocator->em()->getRepository(Agenda::class)->find($request->get('agenda'));

        $this->template = 'admin/page/agenda/period-modal.html.twig';
        $this->arguments['event_start_date'] = $request->get('date');
        $this->arguments['agenda'] = $agenda;
        $this->entity = new Period();
        $this->entity->setAgenda($agenda);
        $this->entity->setPublicationStart(new \DateTime($this->arguments['event_start_date'].'00:00:00'));
        $this->entity->setPublicationEnd(new \DateTime($this->arguments['event_start_date'].'T23:59:00'));
        $this->arguments['form_route'] = $this->generateUrl('admin_agendaperiod_new', [
            'website' => $website->id,
            'agenda' => $agenda->getId(),
            'date' => $this->arguments['event_start_date'],
        ]);

        return parent::new($request);
    }

    /**
     * Edit Item Period.
     */
    #[Route('/{agenda}/edit-item/{period}', name: 'admin_agendaperiod_edit_item', options: ['expose' => true], methods: 'GET|POST')]
    public function editItem(Request $request, Agenda $agenda, Period $period)
    {
        $this->template = 'admin/page/agenda/period-modal.html.twig';

        if (!$agenda instanceof Agenda) {
            throw $this->createNotFoundException(sprintf('Aucune entité trouvée !!'));
        }

        $this->arguments['agenda'] = $agenda;
        $this->entity = $period;
        $this->arguments['form_route'] = $this->generateUrl('admin_agendaperiod_edit_item', [
            'website' => $agenda->getWebsite()->getId(),
            'agenda' => $agenda->getId(),
            'period' => $period->getId(),
        ]);

        return parent::edit($request);
    }

    /**
     * Edit Period.
     *
     * {@inheritdoc}
     *
     * @throws \Exception|\Psr\Cache\InvalidArgumentException
     */
    #[Route('/{agenda}/edit', name: 'admin_agendaperiod_edit', methods: 'GET|POST')]
    public function editAgenda(Request $request, AgendaService $service)
    {
        $this->template = 'admin/page/agenda/calendar.html.twig';
        $agenda = $this->coreLocator->em()->getRepository(Agenda::class)->find($request->get('agenda'));

        if (!$agenda instanceof Agenda) {
            throw $this->createNotFoundException(sprintf('Aucune entité trouvée !!'));
        }

        $this->arguments['agenda'] = $agenda;
        $this->arguments['entities'] = $this->coreLocator->em()->getRepository($this->class)->findBy([
            'agenda' => $agenda->getId(),
        ]);

        $this->arguments = array_merge($this->arguments, $service->eventsDaysData($agenda));

        return parent::edit($request);
    }

    /**
     * Delete Period.
     *
     * {@inheritdoc}
     */
    #[Route('/{delete}/{agendaperiod}', name: 'admin_agendaperiod_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('agenda')) {
            $items[$this->coreLocator->translator()->trans('Agendas', [], 'admin_breadcrumb')] = 'admin_agenda_index';
        }

        parent::breadcrumb($request, $items);
    }
}
