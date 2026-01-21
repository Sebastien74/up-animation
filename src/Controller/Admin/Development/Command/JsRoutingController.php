<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development\Command;

use App\Command\JsRoutingCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * JsRoutingController.
 *
 * To execute fos js-routing commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/commands/js-routing', schemes: '%protocol%')]
class JsRoutingController extends BaseCommand
{
    /**
     * Generate js routes.
     *
     * @throws \Exception
     */
    #[Route('/dump', name: 'js_routing_dump', methods: 'GET')]
    public function dump(Request $request, JsRoutingCommand $cmd, string $projectDir): RedirectResponse
    {
        $this->setFlashBag($cmd->dump(), 'fos:js-routing:dump', $projectDir);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Debug js routes.
     *
     * @throws \Exception
     */
    #[Route('/debug', name: 'js_routing_debug', methods: 'GET')]
    public function debug(Request $request, JsRoutingCommand $cmd, string $projectDir): RedirectResponse
    {
        $this->setFlashBag($cmd->debug(), 'fos:js-routing:debug', $projectDir);
        return $this->redirect($request->headers->get('referer'));
    }
}
