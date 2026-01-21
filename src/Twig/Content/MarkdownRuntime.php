<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Service\Interface\CoreLocatorInterface;
use League\CommonMark\Exception\CommonMarkException;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * MarkdownRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MarkdownRuntime implements RuntimeExtensionInterface
{
    /**
     * AppRuntime constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * To convert markdown content.
     *
     * @throws CommonMarkException
     */
    public function markdown(?string $content = null): ?string
    {
        return $this->coreLocator->markdown()->convertToHtml($content);
    }

    /**
     * To extract H1 in content.
     */
    public function extractMarkdownH1(?string $content = null): ?string
    {
        return $this->coreLocator->markdown()->extractMarkdownH1($content);
    }
}
