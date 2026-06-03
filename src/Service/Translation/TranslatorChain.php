<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Service\Translation\Provider\TranslatorProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * TranslatorChain.
 *
 * Tries each provider in priority order and falls back to the next one on
 * quota exhaustion or failure. Returns an empty array when none succeeds.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class TranslatorChain
{
    /**
     * @param iterable<TranslatorProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly LoggerInterface $translationLogger,
    ) {
    }

    /**
     * @param string[] $texts
     *
     * @return string[]
     */
    public function translate(array $texts, string $source, string $target, bool $html = false): array
    {
        if (!$texts) {
            return [];
        }

        $charCount = array_sum(array_map(static fn ($text): int => mb_strlen((string) $text), $texts));

        foreach ($this->providers as $provider) {
            if ($html && !$provider->supportsHtml()) {
                continue;
            }
            if (!$provider->isAvailable($charCount)) {
                continue;
            }
            try {
                $result = $provider->translate($texts, $source, $target, $html);
                if (\count($result) === \count($texts)) {
                    return array_values($result);
                }
                $this->translationLogger->warning(sprintf('[%s] returned %d/%d items, falling back.', $provider->name(), \count($result), \count($texts)));
            } catch (\Throwable $e) {
                $this->translationLogger->warning(sprintf('[%s] failed, falling back: %s', $provider->name(), $e->getMessage()));
            }
        }

        $this->translationLogger->error(sprintf('No provider available for %s -> %s (%d chars).', $source, $target, $charCount));

        return [];
    }
}
