<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * BlockConfigurationManager.
 *
 * Manage admin Block form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => BlockConfigurationManager::class, 'key' => 'layout_block_configuration_form_manager'],
])]
class BlockConfigurationManager
{
    /**
     * BlockConfigurationManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @onFlush
     */
    public function onFlush(Layout\Block $block, Website $website): void
    {
        $configuration = $website->getConfiguration();
        $classes = $block->getCustomClass() ? explode(' ', $block->getCustomClass()) : [];
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
