<?php

declare(strict_types=1);

namespace App\Service\Core;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * LastRouteService.
 *
 * To register last route in Session
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => LastRouteService::class, 'key' => 'last_route_service'],
])]
class LastRouteService
{
    /**
     * To execute service.
     */
    public function execute(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $uri = $request->getUri();
        $routeName = $request->attributes->get('_route');

        if ($this->isAllowed($request, $routeName, $request->getRequestUri())) {
            $session = $request->getSession();

            if ($routeName[0] === '_') {
                return;
            }

            $securityToken = $_ENV['SECURITY_TOKEN'] ?? '';
            $isAdminPath = $securityToken && str_contains($uri, '/admin-' . $securityToken);

            if ($isAdminPath && str_contains($uri, 'index') && !$request->isMethod('POST')) {
                $page = intval($request->get('page'));
                if ($session->get('last_route_back_page') !== $page) {
                    $session->set('last_route_back_page', $page);
                }
            }

            $routeParams = $request->get('_route_params');
            $routeData = (object) ['name' => $routeName, 'params' => $routeParams];

            /** Do not save same matched route twice */
            $thisRoute = $session->get('this_route');
            if ($thisRoute == $routeData) {
                return;
            }

            if ($session->get('last_uri') !== $uri) {
                $session->set('last_uri', $uri);
            }
            if ($session->get('last_route') !== $thisRoute) {
                $session->set('last_route', $thisRoute);
            }
            if ($session->get('this_route') !== $routeData) {
                $session->set('this_route', $routeData);
            }
            if ($session->get('previous_secure_url') !== $uri) {
                $session->set('previous_secure_url', $uri);
            }

            if ($isAdminPath && is_object($thisRoute) && str_contains($thisRoute->name, 'admin_')) {
                if ($session->get('last_route_back') !== $thisRoute) {
                    $session->set('last_route_back', $thisRoute);
                }
                if ($session->get('this_route_back') !== $routeData) {
                    $session->set('this_route_back', $routeData);
                }
            }

            if (str_contains($uri, 'front') && is_object($thisRoute) && !str_contains($thisRoute->name, 'admin_')) {
                if ($session->get('last_uri_front') !== $uri) {
                    $session->set('last_uri_front', $uri);
                }
                if ($session->get('last_route_front') !== $thisRoute) {
                    $session->set('last_route_front', $thisRoute);
                }
                if ($session->get('this_route_front') !== $routeData) {
                    $session->set('this_route_front', $routeData);
                }
            }
        }
    }

    /**
     * Check if si secure route.
     */
    private function isSecurityRoute(string $routeName): bool
    {
        return str_contains($routeName, 'security');
    }

    /**
     * Check if route is allowed to register in session.
     */
    private function isAllowed(Request $request, ?string $routeName = null, ?string $uri = null): bool
    {
        if (!$routeName || $this->isSecurityRoute($routeName) || '/' === $uri || !$uri) {
            return false;
        }

        static $disabledRoutes = [
            'liip_imagine_filter' => true,
            'fos_js_routing_js' => true,
            'admin_code_generator' => true,
            'admin_mediarelation_reset_media' => true,
            'admin_zone_size' => true,
            'admin_zone_background' => true,
            'admin_col_align' => true,
            'admin_col_background' => true,
            'admin_col_size' => true,
            'admin_cols_positions' => true,
            'admin_block_add' => true,
            'admin_block_edit' => true,
            'admin_blocks_positions' => true,
            'front_gdpr_scripts' => true,
            'front_webmaster_toolbox' => true,
        ];

        if (isset($disabledRoutes[$routeName])) {
            return false;
        }

        static $disabledUris = [
            'ajax',
            'remove',
            'duplicate',
            'modal',
            'delete',
            'reset',
            'front/crypt',
            'urls/status',
            'thumbnails/media',
            'uploads/',
            'webp',
            'png',
            'jpeg',
            'jpg',
            'gif',
            'position',
            'favicon',
        ];

        foreach ($disabledUris as $disabledUri) {
            if (str_contains($uri, $disabledUri)) {
                return false;
            }
        }

        static $adminPatterns = ['edit', 'tree', 'index', 'layout'];
        $registerAdmin = false;
        foreach ($adminPatterns as $pattern) {
            if (str_contains($uri, $pattern)) {
                $registerAdmin = true;
                break;
            }
        }

        $securityToken = $_ENV['SECURITY_TOKEN'] ?? '';
        if ($securityToken && str_contains($uri, '/admin-' . $securityToken) && !$registerAdmin) {
            return false;
        }

        return true;
    }
}
