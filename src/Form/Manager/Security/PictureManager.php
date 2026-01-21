<?php

declare(strict_types=1);

namespace App\Form\Manager\Security;

use App\Entity\Security\Picture;
use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use App\Service\Core\Urlizer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * PictureManager.
 *
 * Manage User Picture
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PictureManager
{
    /**
     * PictureManager constructor.
     */
    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Set User Picture.
     */
    public function execute(User|UserFront $user, FormInterface $form): void
    {
        /** @var UploadedFile $file */
        $file = !empty($form->all()['file']) ? $form->get('file')->getData() : null;

        if ($file instanceof UploadedFile) {
            $filesystem = new Filesystem();
            $picture = $user->getPicture() ? $user->getPicture() : new Picture();
            $extension = $file->guessExtension();
            $filename = Urlizer::urlize(str_replace('.'.$extension, '', $file->getClientOriginalName())).'-'.md5(uniqid()).'.'.$extension;
            $userDirname = $user instanceof User ? 'users' : 'users-front';
            $baseDirname = '/uploads/'.$userDirname.'/'.$user->getSecretKey().'/picture/';
            $baseDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDirname);
            $publicDirname = $this->projectDir.'/public/';
            $publicDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicDirname);
            $dirname = $publicDirname.$baseDirname;

            if ($picture->getDirname() && $filesystem->exists($publicDirname.$picture->getDirname()) && !is_dir($publicDirname.$picture->getDirname())) {
                $filesystem->remove($publicDirname.$picture->getDirname());
            }

            $picture->setFilename($filename);
            $picture->setDirname($baseDirname.$filename);

            if (!$picture->getId()) {
                $picture->setUserFront($user);
                $user->setPicture($picture);
            }

            $file->move($dirname, $filename);
        }
    }
}
