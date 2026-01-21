<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development\Command;

use App\Controller\Admin\AdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * BaseCommand.
 *
 * Base commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BaseCommand extends AdminController
{
    /**
     * Result.
     *
     * @throws \Exception
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/result', name: 'admin_command_result', methods: 'GET', schemes: '%protocol%')]
    public function result(): Response
    {
        return $this->adminRender('admin/page/development/command.html.twig', [
            'disabledFlashBag' => true,
        ]);
    }

    /**
     * Set Command FlashBag.
     */
    protected function setFlashBag(string $response, string $command, string $projectDir): void
    {
        $session = new Session();
        $session->getFlashBag()->add('command', [
            'dirname' => $projectDir,
            'command' => 'php bin/console '.$command,
            'output' => $response,
        ]);
    }
}
