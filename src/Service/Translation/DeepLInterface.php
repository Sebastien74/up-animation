<?php

declare(strict_types=1);

namespace App\Service\Translation;

interface DeepLInterface
{
    public function translate(array $texts, string $targetLang = 'EN'): array;
}
