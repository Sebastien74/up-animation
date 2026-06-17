<?php

declare(strict_types=1);

namespace App\Service\Development;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Twig\Extra\Markdown\MarkdownInterface;

/**
 * DocumentationMarkdownConverter.
 *
 * Backs the markdown_to_html Twig filter for the back-office documentation portal with
 * GitHub-Flavored Markdown, so tables (and task lists, strikethrough, autolinks) render
 * instead of staying as raw "| ... |" text. This filter is used only by the doc portal.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class DocumentationMarkdownConverter implements MarkdownInterface
{
    private readonly GithubFlavoredMarkdownConverter $converter;

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
    }

    public function convert(string $body): string
    {
        return $this->converter->convert($body)->getContent();
    }
}
