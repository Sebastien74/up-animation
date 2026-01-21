<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\Logo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * LogoController.
 *
 * Security Company Logo management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_USERS')]
#[Route('/admin-%security_token%/{website}/security/companies/logo', schemes: '%protocol%')]
class LogoController extends AdminController
{
    /**
     * Delete Logo.
     */
    #[IsGranted('ROLE_DELETE')]
    #[Route('/delete/{logo}', name: 'admin_logo_delete', methods: 'DELETE|GET')]
    public function deleteLogo(Logo $logo, string $projectDir): JsonResponse
    {
        $dirname = $projectDir.'/public/'.$logo->getDirname();
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $filesystem = new Filesystem();

        if ($logo->getDirname() && $filesystem->exists($dirname) && !is_dir($dirname)) {
            $filesystem->remove($dirname);
        }

        $logo->setFilename(null);
        $logo->setDirname(null);

        $this->coreLocator->em()->persist($logo);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }
}
