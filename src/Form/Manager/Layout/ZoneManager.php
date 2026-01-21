<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Layout;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;

/**
 * ZoneManager.
 *
 * Manage admin Zone form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ZoneManager::class, 'key' => 'layout_zone_form_manager'],
])]
class ZoneManager
{
    /**
     * ZoneManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add Zone.
     */
    public function add(Layout\Layout $layout, FormInterface $form): void
    {
        $zone = new Layout\Zone();
        $zone->setPosition(count($layout->getZones()) + 1);

        $layout->addZone($zone);

        /** @var Layout\Grid $grid */
        $grid = $form->getData()['grid'];

        if ($grid) {
            foreach ($grid->getCols() as $key => $gridCol) {
                $position = $key + 1;
                $col = new Layout\Col();
                $col->setPosition($position);
                $col->setSize($gridCol->getSize());
                $zone->addCol($col);
            }
            $this->entityManager->persist($layout);
            $this->entityManager->flush();
        }

        $this->addIntls($layout, $zone);
    }

    /**
     * Add intl[] to Zone.
     */
    private function addIntls(Layout\Layout $layout, Layout\Zone $zone): void
    {
        $website = $layout->getWebsite();
        $locales = $website->getConfiguration()->getLocales();
        foreach ($locales as $locale) {
            $intl = new Layout\ZoneIntl();
            $intl->setLocale($locale);
            $intl->setWebsite($website);
            $zone->addIntl($intl);
        }
    }
}
