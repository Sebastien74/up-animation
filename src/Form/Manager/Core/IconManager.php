<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core\Configuration;
use App\Entity\Core\Icon;
use App\Entity\Core\Website;
use App\Service\Core\Urlizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * IconManager.
 *
 * Manage admin ConfigurationModel form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => IconManager::class, 'key' => 'core_icon_form_manager'],
])]
class IconManager
{
    private string $baseDirname;

    /**
     * IconManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
        $this->baseDirname = '/medias/icons/app';
    }

    /**
     * Execute.
     */
    public function execute(Website $website, FormInterface $form): JsonResponse
    {
        $errors = '';

        if ($form->isValid()) {
            $libraryDirname = $this->projectDir.'/public'.$this->baseDirname;
            $libraryDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $libraryDirname);
            $filesystem = new Filesystem();
            $icons = $form->get('uploadedFile')->getData();
            $configuration = $website->getConfiguration();

            foreach ($icons as $icon) {
                if ($icon instanceof UploadedFile) {
                    $extension = $icon->guessExtension();
                    $originalFilename = pathinfo($icon->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = Urlizer::urlize($originalFilename);
                    $existing = $filesystem->exists($libraryDirname.'/'.$safeFilename.'.'.$extension);
                    $newFilename = $existing ? $safeFilename.'-'.uniqid().'.'.$extension : $safeFilename.'.'.$extension;
                    try {
                        $icon->move($libraryDirname, $newFilename);
                    } catch (FileException $exception) {
                        return new JsonResponse(['success' => false, 'errors' => $exception->getMessage()]);
                    }
                    $this->addIcon($newFilename, $this->baseDirname.'/'.$newFilename, $configuration);
                }
            }

            return new JsonResponse(['success' => true, 'form' => $form]);
        } elseif (!$form->isValid()) {
            foreach ($form->get('uploadedFile')->getErrors() as $error) {
                $errors .= $error->getMessage().'</br>';
            }
        }

        return new JsonResponse(['success' => false, 'errors' => rtrim($errors, '</br>')]);
    }

    /**
     * Add Icon entity.
     */
    public function addIcon(string $newFilename, string $path, Configuration $configuration): void
    {
        $icon = new Icon();
        $icon->setFilename($newFilename);
        $icon->setPath($path);
        $icon->setConfiguration($configuration);

        $filenameMatches = explode('.', $icon->getFilename());
        $icon->setExtension(end($filenameMatches));

        if (preg_match('/icons\/flags/', $icon->getPath())) {
            $icon->setLocale($filenameMatches[0]);
        }

        $this->entityManager->persist($icon);
        $this->entityManager->flush();
    }

    /**
     * Add Icon entity.
     */
    public function remove(string $path, Configuration $configuration): void
    {
        $icon = $this->entityManager->getRepository(Icon::class)->findBy([
            'configuration' => $configuration,
            'path' => $path,
        ]);

        if ($icon) {
            $this->entityManager->remove($icon[0]);
            $this->entityManager->flush();
        }
    }
}
