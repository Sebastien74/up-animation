<?php

declare(strict_types=1);

namespace App\Service\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * RequestParam.
 *
 * Faithful, deprecation-free replacement for Request::get() (attributes, then query, then body).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class RequestParam
{
    public static function get(?Request $request, string $key, mixed $default = null): mixed
    {
        if (!$request instanceof Request) {
            return $default;
        }
        if ($request->attributes->has($key)) {
            return $request->attributes->get($key);
        }
        $query = $request->query->all();
        if (array_key_exists($key, $query)) {
            return $query[$key];
        }
        $body = $request->request->all();
        if (array_key_exists($key, $body)) {
            return $body[$key];
        }

        return $default;
    }
}
