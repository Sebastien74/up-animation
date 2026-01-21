<?php

declare(strict_types=1);

namespace App\Service\Content;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * MarkdownServiceInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => MarkdownServiceInterface::class, 'key' => 'markdown_service'],
])]
interface MarkdownServiceInterface
{
    public function convertToHtml(?string $content = null): ?string;

    public function htmlConvert(string $html): ?string;

    public function extractMarkdownH1(?string $content = null): ?string;
}