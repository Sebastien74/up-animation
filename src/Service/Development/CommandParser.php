<?php

declare(strict_types=1);

namespace App\Service\Development;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * CommandParser.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CommandParser
{
    /**
     * CommandParser constructor.
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly array $excludedNamespaces = [],
    ) {
    }

    /**
     * Execute the console command "list" with XML output to have all available command.
     *
     * @throws \Exception
     */
    public function getCommands(): array
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'list',
            '--format' => 'xml',
        ]);

        $output = new StreamOutput(fopen('php://memory', 'w+'));
        $application->run($input, $output);
        rewind($output->getStream());

        return $this->extractCommandsFromXML(stream_get_contents($output->getStream()));
    }

    /**
     * Chack if command exist.
     *
     * @throws \Exception
     */
    public function getCommand(string $name): bool
    {
        $commands = $this->getCommands();
        foreach ($commands as $group => $commandsGroup) {
            if (isset($commandsGroup[$name])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract an array of available Symfony command from the XML output.
     *
     * @throws \Exception
     */
    private function extractCommandsFromXML($xml): array
    {
        if ('' == $xml) {
            return [];
        }

        $regex = "#<\s*?symfony\b[^>]*>(.*?)</symfony\b[^>]*>#s";
        preg_match($regex, $xml, $matches);
        $xml = trim('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<symfony>'.$matches[1].PHP_EOL.'</symfony>');
        $node = new \SimpleXMLElement($xml);
        $commandsList = [];
        foreach ($node->namespaces->namespace as $namespace) {
            $namespaceId = (string) $namespace->attributes()->id;
            if (!in_array($namespaceId, $this->excludedNamespaces)) {
                foreach ($namespace->command as $command) {
                    $commandsList[$namespaceId][(string) $command] = (string) $command;
                }
            }
        }

        return $commandsList;
    }
}
