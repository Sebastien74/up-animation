<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Structural smoke test over every command shipped in src/Command.
 *
 * Boots the kernel, builds the console Application and asserts each App command
 * is wired, named and exposes a valid definition. No command is executed, so it
 * needs no database and is safe for destructive commands.
 */
final class CommandRegistryTest extends KernelTestCase
{
    public function testEveryAppCommandIsWellFormed(): void
    {
        $application = new Application(self::bootKernel());

        $appCommands = array_filter(
            $application->all(),
            static fn (object $command): bool => str_starts_with($command::class, 'App\\Command\\'),
        );

        self::assertNotEmpty($appCommands, 'No application command was discovered.');

        foreach ($appCommands as $command) {
            $name = (string) $command->getName();
            self::assertNotSame('', $name, sprintf('%s has no name.', $command::class));
            self::assertTrue($command->isEnabled(), sprintf('%s is disabled.', $name));
            // getSynopsis() renders the definition; it throws when configure() is broken.
            self::assertNotSame('', $command->getSynopsis(), sprintf('%s has an empty synopsis.', $name));
        }
    }
}
