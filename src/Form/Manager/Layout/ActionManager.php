<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout\Action;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ActionManager.
 *
 * Manage admin Action configuration form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ActionManager::class, 'key' => 'layout_action_form_manager'],
])]
class ActionManager
{
    /**
     * ActionManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @prePersist
     */
    public function prePersist(Action $action, Website $website): void
    {
        $this->setEntity($action);
        $this->entityManager->persist($action);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Action $action, Website $website): void
    {
        $this->setEntity($action);
        $this->entityManager->persist($action);
    }

    /**
     * Set Entity classname.
     */
    private function setEntity(Action $action): void
    {
        if ($action->getEntity()) {
            $action->setEntity(str_replace('/', '\\', $action->getEntity()));
        }
    }
}
