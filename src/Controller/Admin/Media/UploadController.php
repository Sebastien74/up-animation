<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Form\Interface\MediaFormManagerInterface;
use App\Form\Type\Media\MediaUploadType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
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
     * @throws \Exception
     */
    #[Route('/uploader/{entityId}', name: 'admin_medias_uploader', methods: 'GET|POST')]
    public function uploader(Request $request, Website $website, ?int $entityId = null): JsonResponse|Response
    {
        $entity = $website;
        $entityNamespace = $request->get('entityNamespace');

        if ($entityNamespace && $entityId) {
            $entity = $this->coreLocator->em()->getRepository(urldecode($entityNamespace))->find($entityId);
        }

        $form = $this->createForm(MediaUploadType::class, $entity, [
            'data_class' => $this->coreLocator->em()->getMetadataFactory()->getMetadataFor(get_class($entity))->getName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->mediaLocator->media()->post($form, $website);
            $this->coreLocator->em()->persist($entity);
            $this->coreLocator->em()->flush();

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
     * File downloader.
     */
    #[Route('/download', name: 'admin_medias_downloader', methods: 'GET')]
    public function downloader(Request $request, string $projectDir): RedirectResponse|Response
    {
        $mimeTypes = ['csv' => 'text/csv'];
        $fileDirname = $projectDir.'/public/'.ltrim(urldecode($request->get('fileDirname')), '/');
        $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);
        $filesystem = new Filesystem();

        if ($filesystem->exists($fileDirname)) {
            $file = new File($fileDirname);
            $response = new Response(file_get_contents($file->getRealPath()));
            $mimeType = !empty($mimeTypes[$file->getExtension()]) ? $mimeTypes[$file->getExtension()] : $file->getMimeType();
            $headers = [
                'Expires' => 'Tue, 01 Jul 1970 06:00:00 GMT',
                'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
                'Content-Disposition' => 'attachment; filename='.$file->getFilename(),
                'Content-Type' => $mimeType,
                'Content-Transfer-Encoding' => 'binary',
            ];
            foreach ($headers as $key => $val) {
                $response->headers->set($key, $val);
            }

            return $response;
        }

        return $this->redirect($request->headers->get('referer'));
    }
}
