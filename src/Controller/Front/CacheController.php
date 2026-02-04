<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Controller\BaseController;
use App\Entity\Core\Configuration;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CacheController.
 *
 * Manage render cache
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CacheController extends BaseController
{
    private const int CACHE_EXPIRES = 3600;
    private const string CHARSET = 'UTF-8';

    /**
     * CacheController constructor.
     */
    public function __construct(protected CoreLocatorInterface $coreLocator)
    {
        parent::__construct($coreLocator);
    }

    /**
     * Get cache.
     *
     * @throws Exception
     */
    protected function cache(Request $request, string $template, mixed $entity = null, array $arguments = []): JsonResponse|Response|null
    {
        $isAjax = $request->query->get('scroll-ajax') || $request->query->get('ajax');
        
        if ($isAjax) {
            $response = new JsonResponse(['html' => $this->renderView($template, $arguments)]);
        } else {
            $response = $this->render($template, $arguments);
        }

        // Set cache headers for 1 week
        $response->setPublic();
        $response->setMaxAge(604800);
        $response->setSharedMaxAge(604800);

        return $response;
    }

    /**
     * Get cache expiration.
     *
     * @throws Exception
     */
    private function getCacheExpires(Configuration $configuration): object
    {
        $cacheConfiguration = $configuration?->getCacheExpiration();
        $cacheExpires = $cacheConfiguration ?: self::CACHE_EXPIRES;
        $date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $date->modify('+'.$cacheExpires.' seconds');

        return (object) [
            'result' => $cacheExpires,
            'date' => $date,
        ];
    }

    /**
     * Get Charset.
     */
    private function getCharset(Configuration $configuration): string
    {
        return $configuration->getCharset() ?: self::CHARSET;
    }
}
