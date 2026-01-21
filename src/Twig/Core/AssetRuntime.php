<?php

declare(strict_types=1);

namespace App\Twig\Core;

use Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * AssetRuntime.
 *
 * Manage assets twig extension
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AssetRuntime implements RuntimeExtensionInterface
{
    /**
     * AssetRuntime constructor.
     */
    public function __construct(private readonly EntryFilesTwigExtension $entryFilesTwig)
    {
    }

    /**
     * Get javascript render files.
     */
    public function javascriptFiles(array $entries = []): array
    {
        $files = [];
        foreach ($entries as $entry) {
            if (!empty($entry['entryName']) && !empty($entry['webpack'])) {
                $entryFiles = $this->entryFilesTwig->getWebpackJsFiles($entry['entryName'], $entry['webpack']);
                foreach ($entryFiles as $entryFile) {
                    $entry['strategy'] = !isset($entry['strategy']) ? 'default' : $entry['strategy'];
                    $entry['defer'] = !isset($entry['defer']) ? true : $entry['defer'];
                    $entry['comment'] = !isset($entry['comment']) ? null : $entry['comment'];
                    $entry['file'] = $entryFile;
                    $files[] = $entry;
                }
            }
        }

        return $files;
    }
}
