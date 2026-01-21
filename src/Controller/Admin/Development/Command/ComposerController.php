<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development\Command;

use App\Command\ComposerCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ComposerController.
 *
 * To execute debug commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/commands/composer', schemes: '%protocol%')]
class ComposerController extends BaseCommand
{
    /**
     * Autoload.
     *
     * @throws \Exception
     */
    #[Route('/autoload', name: 'composer_autoload', methods: 'GET')]
    public function autoload(Request $request, ComposerCommand $cmd, string $projectDir): RedirectResponse
    {
        $this->setFlashBag($cmd->autoload(), 'dump-autoload --no-dev --classmap-authoritative', $projectDir);
        return $this->redirect($request->headers->get('referer'));
    }
}
