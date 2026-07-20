<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\Content\LocationTokenService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * LocationTokenSubscriber.
 *
 * Substitue le token %location% dans le HTML final d'une fiche produit
 * (title, meta, canonical, H1, intro, body) selon les segments d'URL
 * {agency}/{location}. Chaque variante ayant une URL distincte, la
 * substitution est cachée par URL (#[Cache] du contrôleur).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class LocationTokenSubscriber implements EventSubscriberInterface
{
    private const array ROUTES = [
        'front_catalogproduct_view', 'front_catalogproduct_view_only',
        'front_newscast_view', 'front_newscast_view_only',
    ];

    public function __construct(
        private readonly LocationTokenService $locationTokenService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onResponse', -10]];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!in_array($request->attributes->get('_route'), self::ROUTES, true)) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();
        if (!is_string($content)) {
            return;
        }

        $location = $request->attributes->get('location');
        $hasLocation = is_string($location) && '' !== $location;
        $hasToken = str_contains($content, LocationTokenService::TOKEN);
        if (!$hasLocation && !$hasToken) {
            return;
        }

        if ($hasToken) {
            $content = $this->locationTokenService->apply($content);
        }

        // Variante localisée : canonical AUTO-RÉFÉRENT (l'URL de la variante elle-même),
        // sinon toutes les variantes canonicalisent vers la base et ne sont jamais indexées.
        if ($hasLocation) {
            // Échapper l'URL (dérivée de la requête → contrôlable) avant injection HTML,
            // et utiliser un callback pour neutraliser les métacaractères de remplacement ($, \).
            $canonicalUrl = htmlspecialchars(
                $request->getSchemeAndHttpHost().$request->getPathInfo(),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
            $content = preg_replace_callback(
                '#(<link\s+rel="canonical"\s+href=")[^"]*(")#i',
                static fn (array $m): string => $m[1].$canonicalUrl.$m[2],
                $content,
                1
            ) ?? $content;
        }

        $response->setContent($content);
    }
}
