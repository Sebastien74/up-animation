<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

final class NullTranslationLoader implements LoaderInterface
{
    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        return new MessageCatalogue($locale, [$domain => []]);
    }
}
