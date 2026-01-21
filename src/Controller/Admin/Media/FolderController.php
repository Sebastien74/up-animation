<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Media\Folder;
use App\Entity\Media\Media;
use App\Form\Interface\MediaFormManagerInterface;
use App\Form\Type\Media\FolderType;
use App\Form\Type\Media\SearchType;
use App\Form\Type\Media\SelectFolderType;
use App\Repository\Media\FolderRepository;
use App\Repository\Media\MediaRepository;
use App\Service\Core\Uploader;
use App\Service\Development\FileUrlizerService;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FolderController.
 *
 * Media Folder management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MEDIA')]
#[Route('/admin-%security_token%/{website}/medias/folders', schemes: '%protocol%')]
class FolderController extends AdminController
{
    protected ?string $class = Folder::class;
    protected ?string $formType = FolderType::class;

    /**
     * FolderController constructor.
     */
    public function __construct(
        protected MediaFormManagerInterface $mediaLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * New Folder.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_folder_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        $this->entity = new $this->class();

        return parent::new($request);
    }

    /**
     * Edit Folder.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{folder}', name: 'admin_folder_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Delete Folder.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{folder}', name: 'admin_folder_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $folder = $this->coreLocator->em()->getRepository(Folder::class)->find($request->get('folder'));
        if ($folder instanceof Folder && !$folder->getFolders()->isEmpty()) {
            return new JsonResponse([
                'alert' => 'error',
                'message' => $this->coreLocator->translator()->trans('Le dossier "%folderName%" contient des sous-dossiers, vous ne pouvez pas le supprimer.', ['%folderName%' => $folder->getAdminName()], 'admin'),
            ]);
        } elseif ($folder instanceof Folder && !$folder->getMedias()->isEmpty()) {
            return new JsonResponse([
                'alert' => 'error',
                'message' => $this->coreLocator->translator()->trans('Le dossier "%folderName%" contient des médias, vous ne pouvez pas le supprimer.', ['%folderName%' => $folder->getAdminName()], 'admin'),
            ]);
        }

        return parent::delete($request);
    }

    /**
     * Select folder.
     *
     * @throws \Exception
     */
    #[Route('/select', name: 'admin_folder_select', methods: 'GET')]
    public function select(): JsonResponse
    {
        $form = $this->createForm(SelectFolderType::class);

        return new JsonResponse(['html' => $this->renderView('admin/page/media/select-folder.html.twig', [
            'form' => $form->createView(),
        ])]);
    }

    /**
     * Move in folder.
     */
    #[Route('/move/{media}/{folderId}',
        name: 'admin_folder_media_move',
        options: ['expose' => true],
        defaults: ['folderId' => null],
        methods: 'GET')]
    public function move(FolderRepository $folderRepository, Media $media, ?int $folderId = null): JsonResponse
    {
        $folder = $folderId ? $folderRepository->find($folderId) : null;
        $media->setFolder($folder);
        $this->coreLocator->em()->persist($media);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Zip medias Folder.
     */
    #[Route('/zip/{folder}', name: 'admin_folder_zip', methods: 'GET')]
    public function zip(
        Request $request,
        MediaRepository $mediaRepository,
        Uploader $uploader,
        FileUrlizerService $fileUrlizerService,
        string $projectDir,
        Folder $folder)
    {
        $medias = $mediaRepository->findBy(['folder' => $folder]);
        $websiteDirname = $projectDir.'/public/uploads/'.$folder->getWebsite()->getUploadDirname().'/';
        $websiteDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $websiteDirname);
        $zipName = Urlizer::urlize($folder->getAdminName()).'.zip';
        $tmpDirname = $projectDir.'/public/uploads/tmp/medias-zip/';
        $tmpDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tmpDirname);
        $session = new Session();

        if ($medias) {
            foreach ($medias as $media) {
                $fileDirname = $websiteDirname.$media->getFilename();
                $uploader->pathToUploadedFile($fileDirname, true, $tmpDirname);
            }
            $zip = $fileUrlizerService->zip($tmpDirname, $zipName);
            if ($zip) {
                $response = new Response(file_get_contents($zip));
                $response->headers->set('Content-Type', 'application/zip');
                $response->headers->set('Content-Disposition', 'attachment;filename="'.$zip.'"');
                $response->headers->set('Content-length', strval(filesize($zip)));
                @unlink($zipName);
                $filesystem = new Filesystem();
                if ($filesystem->exists($tmpDirname)) {
                    $filesystem->remove($tmpDirname);
                }

                return $response;
            } else {
                $session->getFlashBag()->add('info', $this->coreLocator->translator()->trans('Une erreur est survenue !!', [], 'admin'));

                return $this->redirect($request->headers->get('referer'));
            }
        }

        $session->getFlashBag()->add('info', $this->coreLocator->translator()->trans('Aucun fichier trouvé !!', [], 'admin'));

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Search medias.
     */
    #[Route('/search', name: 'admin_folders_medias_search', methods: 'GET')]
    public function search(Request $request, PaginatorInterface $paginator): JsonResponse|Response
    {
        $template = 'admin/page/media/search.html.twig';
        $form = $this->createForm(SearchType::class, null, ['method' => 'GET']);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $medias = $this->mediaLocator->search()->search($form, $this->coreLocator->website()->entity);
            $medias = $paginator->paginate(
                $medias,
                $this->coreLocator->request()->query->getInt('page', 1),
                24,
                ['wrap-queries' => true]
            );

            return new JsonResponse(['html' => $this->renderView($template, ['medias' => $medias])]);
        }

        return $this->adminRender($template, ['form' => $form->createView()]);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('folder')) {
            $items[$this->coreLocator->translator()->trans('Médias', [], 'admin_breadcrumb')] = 'admin_medias_library';
        }

        parent::breadcrumb($request, $items);
    }
}
