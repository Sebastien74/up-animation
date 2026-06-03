<?php

declare(strict_types=1);

namespace App\Service\Translation\Provider;

/**
 * TranslatorProviderInterface.
 *
 * A machine-translation backend usable in the fallback chain.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface TranslatorProviderInterface
{
    public function name(): string;

    public function supportsHtml(): bool;

    public function isAvailable(int $charCount): bool;

    /**
     * Translate texts, preserving order and count.
     *
     * @param string[] $texts
     *
     * @return string[]
     */
    public function translate(array $texts, string $source, string $target, bool $html = false): array;
}
