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
        $routeName = $request->get('_route');

        if ($this->isAllowed($request, $routeName, $request->getRequestUri())) {
            $session = $request->getSession();

            $routeParams = $request->get('_route_params');
            if ('_' == $routeName[0]) {
                return;
            }

            if (preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $uri) && str_contains($uri, 'index') && !$request->isMethod('POST')) {
                $session->set('last_route_back_page', intval($request->get('page')));
            }

            $routeData = (object) ['name' => $routeName, 'params' => $routeParams];

            /** Do not save same matched route twice */
            $thisRoute = $session->get('this_route', []);
            if ($thisRoute == $routeData) {
                return;
            }

            $session->set('last_uri', $uri);
            $session->set('last_route', $thisRoute);
            $session->set('this_route', $routeData);
            $session->set('previous_secure_url', $uri);

            if (preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $uri) && is_object($thisRoute) && str_contains($thisRoute->name, 'admin_')) {
                $session->set('last_route_back', $thisRoute);
                $session->set('this_route_back', $routeData);
            }

            if (str_contains($uri, 'front') && is_object($thisRoute) && !str_contains($thisRoute->name, 'admin_')) {
                $session->set('last_uri_front', $uri);
                $session->set('last_route_front', $thisRoute);
                $session->set('this_route_front', $routeData);
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

        $disabledRoutes = [
            'liip_imagine_filter',
            'fos_js_routing_js',
            'admin_code_generator',
            'admin_mediarelation_reset_media',
            'admin_zone_size',
            'admin_zone_background',
            'admin_col_align',
            'admin_col_background',
            'admin_col_size',
            'admin_cols_positions',
            'admin_block_add',
            'admin_block_edit',
            'admin_blocks_positions',
            'front_gdpr_scripts',
            'front_webmaster_toolbox',
        ];

        if (in_array($routeName, $disabledRoutes)) {
            return false;
        }

        $disabledUris = [
            'ajax',
            'remove',
            'duplicate',
            'modal',
            'delete',
            'reset',
            'front\/crypt',
            'urls\/status',
            'thumbnails\/media',
            'uploads\/',
            'webp',
            'png',
            'jpeg',
            'jpg',
            'gif',
            'position',
            'favicon',
        ];

        foreach ($disabledUris as $disabledUri) {
            if (preg_match('/'.$disabledUri.'/', $uri)) {
                return false;
            }
        }

        $adminPatterns = ['edit', 'tree', 'index', 'layout'];
        $registerAdmin = false;
        foreach ($adminPatterns as $pattern) {
            if (preg_match('/'.$pattern.'/', $uri)) {
                $registerAdmin = true;
                break;
            }
        }

        if (preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $uri) && !$registerAdmin) {
            return false;
        }

        return true;
    }
}
