<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\ScheduledCommand;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Service\Core\Urlizer;
use App\Service\Development\ScheduledCommandCatalog;
use App\Service\Development\ScheduledCommandDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * CommandFixtures.
 *
 * Command Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CommandFixtures::class, 'key' => 'command_fixtures'],
])]
readonly class CommandFixtures
{
    /**
     * CommandFixtures constructor.
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScheduledCommandCatalog $catalog,
    ) {
    }

    /**
     * Add ScheduledCommand[].
     */
    public function add(Website $website, ?User $user = null): void
    {
        foreach ($this->catalog->all() as $definition) {
            $this->addScheduledCommand($website, $definition, $user);
        }
    }

    /**
     * Add ScheduledCommand.
     */
    private function addScheduledCommand(Website $website, ScheduledCommandDefinition $definition, ?User $user = null): void
    {
        $command = new ScheduledCommand();
        $command->setWebsite($website);
        $command->setCreatedBy($user);
        $command->setAdminName($definition->name);
        $command->setCommand($definition->command);
        $command->setCronExpression($definition->cronExpression);
        $command->setDescription($definition->description);
        $command->setLogFile(Urlizer::urlize($definition->command).'.log');
        $command->setActive($definition->active);

        $this->entityManager->persist($command);
        $this->entityManager->flush();
    }
}
