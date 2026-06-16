<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

/**
 * PageSpeedException.
 *
 * Raised when a PageSpeed Insights measurement cannot be produced (feature disabled,
 * misconfigured API key or upstream API failure), with a user-facing message.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PageSpeedException extends \RuntimeException
{
    public static function disabled(): self
    {
        return new self('PageSpeed Insights n\'est pas configuré (clé API manquante ou désactivé).');
    }

    public static function api(int $status, ?string $detail = null): self
    {
        $message = sprintf('PageSpeed Insights a répondu une erreur (HTTP %d).', $status);
        if (null !== $detail && '' !== $detail) {
            $message .= ' '.$detail;
        }

        return new self($message);
    }

    public static function transport(string $detail): self
    {
        return new self('PageSpeed Insights est injoignable : '.$detail);
    }
}
