<?php

declare(strict_types=1);

namespace App\Service\Development;

use App\Entity\Core\ScheduledCommand;
use App\Entity\Core\Website;
use App\Service\Core\Urlizer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * ScheduledCommandInstaller.
 *
 * Idempotent provisioning of the catalog scheduled commands into the database.
 * Shared by the install command (manual) and the scheduler engine (auto-provision
 * on run), so a freshly added catalog command lands on existing websites without
 * a manual step. Each (website, command) pair is checked first.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ScheduledCommandInstaller
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ScheduledCommandCatalog $catalog,
    ) {
    }

    /**
     * Create the missing catalog commands. Returns the number created.
     *
     * @param bool      $all         install every definition, not only the active defaults
     * @param bool|null $forceActive null keeps each definition's own default state
     */
    public function installMissing(?Website $website = null, bool $all = false, ?bool $forceActive = null): int
    {
        $websites = $website instanceof Website
            ? [$website]
            : $this->entityManager->getRepository(Website::class)->findAll();

        if ([] === $websites) {
            return 0;
        }

        $definitions = $all ? $this->catalog->all() : $this->catalog->defaults();
        $scheduledRepository = $this->entityManager->getRepository(ScheduledCommand::class);

        $created = 0;
        foreach ($websites as $site) {
            foreach ($definitions as $definition) {
                $existing = $scheduledRepository->findOneBy([
                    'website' => $site,
                    'command' => $definition->command,
                ]);
                if (null !== $existing) {
                    continue;
                }

                $this->entityManager->persist($this->build($site, $definition, $forceActive));
                ++$created;
            }
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $created;
    }

    private function build(Website $website, ScheduledCommandDefinition $definition, ?bool $forceActive): ScheduledCommand
    {
        return (new ScheduledCommand())
            ->setWebsite($website)
            ->setAdminName($definition->name)
            ->setCommand($definition->command)
            ->setCronExpression($definition->cronExpression)
            ->setDescription($definition->description)
            ->setLogFile(Urlizer::urlize($definition->command).'.log')
            ->setActive($forceActive ?? $definition->active);
    }
}
