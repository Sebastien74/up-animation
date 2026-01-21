<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Module\Agenda\Agenda;
use Exception;

/**
 * AgendaService.
 *
 * To manage Agenda
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AgendaService
{
    /**
     * Get all period[] data of an Agenda.
     *
     * @throws Exception
     */
    public function eventsDaysData(Agenda $agenda): array
    {
        $currentDay = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $interval = new \DateInterval('P1D');
        $response['eventsDaysData'] = [];

        foreach ($agenda->getPeriods() as $period) {
            $realEnd = $period->getPublicationEnd();
            $datePeriod = new \DatePeriod($period->getPublicationStart(), $interval, $realEnd);
            foreach ($datePeriod as $date) {
                $response['eventsDaysData'][$date->format('Y-m-d')] = $period->getId();
                if ($date->format('Y-m-d') === $currentDay->format('Y-m-d')) {
                    $response['period'] = $period;
                }
            }
        }

        return $response;
    }
}
