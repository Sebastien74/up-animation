<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development\Command;

use App\Command\DebugCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DebugController.
 *
 * To execute debug commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/commands/debug', schemes: '%protocol%')]
class DebugController extends BaseCommand
{
    /**
     * Debug.
     */
    #[Route('/debug/{service}', name: 'bin_debug', methods: 'GET')]
    public function debug(Request $request, DebugCommand $cmd, string $service, string $projectDir): RedirectResponse
    {
        $this->setFlashBag($cmd->debug($service), 'debug:'.$service, $projectDir);
        return $this->redirectToRoute('admin_command_result');
    }
}
