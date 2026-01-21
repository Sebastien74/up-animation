<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\Picture;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * PictureController.
 *
 * Security User Picture management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_USERS')]
#[Route('/admin-%security_token%/{website}/security/users/picture', schemes: '%protocol%')]
class PictureController extends AdminController
{
    /**
     * Delete Picture.
     */
    #[IsGranted('ROLE_DELETE')]
    #[Route('/delete/{picture}', name: 'admin_userpicture_delete', methods: 'DELETE')]
    public function deletePicture(Picture $picture, string $projectDir): JsonResponse
    {
        $dirname = $projectDir.'/public/'.$picture->getDirname();
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $filesystem = new Filesystem();

        if ($picture->getDirname() && $filesystem->exists($dirname) && !is_dir($dirname)) {
            $filesystem->remove($dirname);
        }

        $picture->setFilename(null);
        $picture->setDirname(null);

        $this->coreLocator->em()->persist($picture);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }
}
