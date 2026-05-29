<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Form\Interface\MediaFormManagerInterface;
use App\Form\Type\Media\MediaUploadType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * UploadController.
 *
 * Media upload management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MEDIA')]
#[Route('/admin-%security_token%/{website}/medias/upload', schemes: '%protocol%')]
class UploadController extends AdminController
{
    /**
     * UploadController constructor.
     */
    public function __construct(
        protected MediaFormManagerInterface $mediaLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Medias Uploader.
     *
     * @throws Exception
     */
    #[Route('/uploader/{entityId}', name: 'admin_medias_uploader', methods: 'GET|POST')]
    public function uploader(Request $request, Website $website, ?int $entityId = null): JsonResponse|Response
    {
        $entity = $website;
        $entityNamespace = $request->query->get('entityNamespace');
        $entityNamespace = is_string($entityNamespace) ? urldecode($entityNamespace) : null;

        // Only accept namespaces that are managed App\Entity\... classes; the
        // value is user-controlled so we must not pass it raw to autoloading.
        if ($entityNamespace && $entityId) {
            if (!preg_match('/^App\\\\Entity(\\\\[A-Za-z0-9_]+)+$/', $entityNamespace)
                || !$this->coreLocator->em()->getMetadataFactory()->hasMetadataFor($entityNamespace)) {
                throw $this->createNotFoundException();
            }
            $entity = $this->coreLocator->em()->getRepository($entityNamespace)->find($entityId);
            if (!$entity) {
                throw $this->createNotFoundException();
            }
            $this->denyUnlessEntityWebsite($entity);
        }

        $form = $this->createForm(MediaUploadType::class, $entity, [
            'data_class' => $this->coreLocator->em()->getMetadataFactory()->getMetadataFor(get_class($entity))->getName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->mediaLocator->media()->post($form, $website);
            $this->coreLocator->em()->persist($entity);
            try {
                $this->coreLocator->em()->flush();
            } catch (\Doctrine\DBAL\Exception\DeadlockException $e) {
                $this->coreLocator->em()->flush();
            }
            return new JsonResponse(['success' => true, 'form' => $form['medias']]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $errors = '';
            foreach ($form->getErrors() as $error) {
                $errors .= $error->getMessage().'</br>';
            }
            foreach ($form['medias']['uploadedFile']->getErrors() as $error) {
                $errors .= $error->getMessage().'</br>';
            }
            return new JsonResponse(['success' => false, 'errors' => rtrim($errors, '</br>')]);
        }

        return $this->adminRender('admin/core/form/dropzone.html.twig', [
            'form' => $form->createView(),
            'entityNamespace' => $entityNamespace,
            'website' => $this->getWebsite(),
            'interface' => $entityNamespace ? $this->getInterface(urldecode($entityNamespace)) : [],
            'entityId' => $entityId,
        ]);
    }

    /**
     * File downloader. Serves files strictly under public/ - the requested
     * path is resolved with realpath() and rejected if it escapes that root.
     */
    #[Route('/download', name: 'admin_medias_downloader', methods: 'GET')]
    public function downloader(Request $request, string $projectDir): RedirectResponse|Response
    {
        $mimeTypes = ['csv' => 'text/csv'];
        $publicRoot = realpath($projectDir.'/public');
        $requested = (string) $request->get('fileDirname');

        if ('' === $requested || false === $publicRoot) {
            throw $this->createNotFoundException();
        }

        // Reject NUL bytes and obvious traversal sequences before resolving.
        if (str_contains($requested, "\0")) {
            throw $this->createNotFoundException();
        }

        $candidate = $projectDir.'/public/'.ltrim(urldecode($requested), '/');
        $candidate = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
        $resolved = realpath($candidate);

        $filesystem = new Filesystem();
        $publicRootNormalized = rtrim($publicRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (false === $resolved || !$filesystem->exists($resolved) || !str_starts_with($resolved, $publicRootNormalized)) {
            return $this->safeRefererRedirect($request, 'admin_dashboard', ['website' => $this->getWebsite()->id]);
        }

        $file = new File($resolved);
        $response = new Response(file_get_contents($file->getRealPath()));
        $mimeType = !empty($mimeTypes[$file->getExtension()]) ? $mimeTypes[$file->getExtension()] : $file->getMimeType();
        $headers = [
            'Expires' => 'Tue, 01 Jul 1970 06:00:00 GMT',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
            'Content-Disposition' => 'attachment; filename="'.basename($file->getFilename()).'"',
            'Content-Type' => $mimeType,
            'Content-Transfer-Encoding' => 'binary',
        ];
        foreach ($headers as $key => $val) {
            $response->headers->set($key, $val);
        }

        return $response;
    }
}
