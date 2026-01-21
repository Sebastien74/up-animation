<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * ArrayToStringTransformer.
 *
 * Transform array to string
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ArrayToStringTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }
        return implode(', ', $value);
    }

    public function reverseTransform(mixed $value): array
    {
        if (!$value) {
            return [];
        }
        return explode(', ', $value);
    }
}
