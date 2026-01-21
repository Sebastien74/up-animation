<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Seo\Url;
use App\Service\Content\SeoService;
use Psr\Cache\InvalidArgumentException;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * SeoRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SeoRuntime implements RuntimeExtensionInterface
{
    /**
     * ApiRuntime constructor.
     */
    public function __construct(private readonly SeoService $seoService)
    {
    }

    /**
     * Get Seo.
     */
    public function seo(Url $url, mixed $entity, bool $asIndexMicrodata = false): bool|array
    {
        try {
            return $this->seoService->execute($url, $entity, null, false, null, [], $asIndexMicrodata);
        } catch (\Exception|InvalidArgumentException $e) {
        }

        return false;
    }
}
