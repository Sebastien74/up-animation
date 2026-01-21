<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development\Command;

use App\Command\AssetsCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AssetsController.
 *
 * To execute assets commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/commands/assets', schemes: '%protocol%')]
class AssetsController extends BaseCommand
{
    /**
     * Install assets.
     *
     * @throws \Exception
     */
    #[Route('/install', name: 'assets_install', methods: 'GET')]
    public function install(Request $request, AssetsCommand $cmd, string $projectDir): RedirectResponse
    {
        $this->setFlashBag($cmd->install(), 'assets:install --symlink --relative web', $projectDir);
        return $this->redirect($request->headers->get('referer'));
    }
}
