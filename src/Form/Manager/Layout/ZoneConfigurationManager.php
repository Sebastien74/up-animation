<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ZoneConfigurationManager.
 *
 * Manage admin Zone form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ZoneConfigurationManager::class, 'key' => 'layout_zone_configuration_form_manager'],
])]
class ZoneConfigurationManager
{
    /**
     * ZoneConfigurationManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @onFlush
     */
    public function onFlush(Layout\Zone $zone, Website $website): void
    {
        $configuration = $website->getConfiguration();
        $classes = $zone->getCustomClass() ? explode(' ', $zone->getCustomClass()) : [];
        foreach ($classes as $class) {
            if ($class) {
                $existing = $this->entityManager->getRepository(Layout\CssClass::class)->findOneBy([
                    'configuration' => $configuration,
                    'name' => $class,
                ]);
                if (!$existing) {
                    $cssClass = new Layout\CssClass();
                    $cssClass->setConfiguration($configuration)->setName($class);
                    $this->entityManager->persist($cssClass);
                    $this->entityManager->flush();
                }
            }
        }
    }
}
