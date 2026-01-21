<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development\Command;

use App\Command\DoctrineCommand;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DoctrineController.
 *
 * To execute doctrine commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/commands/doctrine', schemes: '%protocol%')]
class DoctrineController extends BaseCommand
{
    /**
     * Update DB.
     *
     * @throws \Exception
     */
    #[Route('/update', name: 'doctrine_schema_update', methods: 'GET')]
    public function update(Request $request, DoctrineCommand $cmd, string $projectDir): RedirectResponse
    {
        $this->setFlashBag($cmd->update(), 'doctrine:schema:update --force --complete', $projectDir);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Clear cache result.
     *
     * @throws \Exception
     */
    #[Route('/clear/cache/result', name: 'doctrine_clear_cache_result', methods: 'GET')]
    public function cacheClearResult(Request $request, DoctrineCommand $cmd, string $projectDir): RedirectResponse
    {
        $this->setFlashBag($cmd->cacheClearResult(), 'doctrine:cache:clear-result', $projectDir);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Schema validation.
     *
     * @throws \Exception
     */
    #[Route('/validate', name: 'doctrine_schema_validate', methods: 'GET')]
    public function validate(Request $request, DoctrineCommand $cmd, string $projectDir): RedirectResponse
    {
        $this->setFlashBag($cmd->validate(), 'doctrine:schema:validate', $projectDir);
        return $this->redirect($request->headers->get('referer'));
    }
}
