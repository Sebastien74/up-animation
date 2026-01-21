<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\ScheduledCommand;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Service\Core\Urlizer;
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
class CommandFixtures
{
    /**
     * CommandFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add ScheduledCommand[].
     */
    public function add(Website $website, ?User $user = null): void
    {
        foreach ($this->getScheduledConfiguration() as $configuration) {
            $this->addScheduledCommand($website, $configuration, $user);
        }
    }

    /**
     * Add ScheduledCommand.
     */
    private function addScheduledCommand(Website $website, array $configuration, ?User $user = null): void
    {
        $command = new ScheduledCommand();
        $command->setWebsite($website);
        $command->setCreatedBy($user);
        $command->setAdminName($configuration['name']);
        $command->setCommand($configuration['command']);
        $command->setCronExpression($configuration['expression']);
        $command->setDescription($configuration['description']);
        $command->setLogFile(Urlizer::urlize($configuration['command']).'.log');
        $command->setActive(isset($configuration['active']) && $configuration['active']);

        $this->entityManager->persist($command);
        $this->entityManager->flush();
    }

    /**
     * Get Schedules configuration.
     */
    private function getScheduledConfiguration(): array
    {
        return [
            ['name' => 'Suppression des données RGPD', 'command' => 'gdpr:remove', 'expression' => '00 1 * * *', 'description' => 'Supprime les données personnelles tous les jours à 1H du matin'],
            ['name' => 'Suppression des tokens utilisateurs', 'command' => 'security:reset:token', 'expression' => '* * * * *', 'description' => 'Suppression des tokens de plus de 2H'],
            ['name' => 'Alertes expiration des mots de passe utilisateurs', 'command' => 'security:password:expire', 'expression' => '00 11 * * *', 'description' => "Envoi d'emails (arrive à expiration & à expiré) tous les jours à 11H le matin"],
            ['name' => 'Synchronisation des Social walls', 'command' => 'social-wall:synchronization', 'expression' => '* * * * *', 'description' => 'Mise à jour des social wall toutes les minutes'],
        ];
    }
}
