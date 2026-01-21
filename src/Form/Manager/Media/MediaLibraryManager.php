<?php

declare(strict_types=1);

namespace App\Form\Manager\Media;

use App\Entity\Core\Website;
use App\Entity\Media\Media;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MediaLibraryManager.
 *
 * Manage admin Media library form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => MediaLibraryManager::class, 'key' => 'media_library_form_manager'],
])]
class MediaLibraryManager
{
    /**
     * MediaLibraryManager constructor.
     */
    public function __construct(
        private readonly string $projectDir,
        private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Media $media, Website $website): void
    {
        $this->renameFile($media, $website);
        foreach ($media->getMediaScreens() as $mediaScreen) {
            $this->renameFile($mediaScreen, $website);
        }
    }

    /**
     * Rename file.
     */
    private function renameFile(Media $media, Website $website): void
    {
        $name = $media->getName();
        $filename = $media->getFilename();
        $extension = $media->getExtension();

        if (empty($extension) || !preg_match('/.'.$extension.'/', $filename)) {
            $matches = $filename ? explode('.', $filename) : [];
            $extension = end($matches);
        }

        $originalName = $filename ? str_replace('.'.$extension, '', $filename) : null;

        if ($name !== $originalName && $filename) {
            $baseDirname = $this->projectDir.'/public/uploads/'.$website->getUploadDirname().'/';
            $baseDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDirname);
            $filesystem = new Filesystem();
            $newFileDirname = $baseDirname.$name.'.'.$extension;
            if ($filesystem->exists($newFileDirname)) {
                $media->setName($originalName);
                $message = $this->translator->trans('Un autre fichier porte déjà ce nom', [], 'admin').' : '.$name.'.'.$extension;
                $session = new Session();
                $session->getFlashBag()->add('error', $message);
                $session->set('same_file_error', $message);
            } else {
                $filesystem->rename($baseDirname.$filename, $newFileDirname);
                $media->setFilename($name.'.'.$extension);
            }
        }
    }
}
