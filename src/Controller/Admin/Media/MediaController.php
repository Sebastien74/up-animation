<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Layout\Page;
use App\Entity\Media\Folder;
use App\Entity\Media\Media;
use App\Entity\Module\Catalog as CatalogEntities;
use App\Entity\Module\Newscast as NewscastEntities;
use App\Form\Interface\MediaFormManagerInterface;
use App\Form\Type\Media\SearchType;
use App\Form\Widget\MediaType;
use App\Service\Core\Urlizer;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * MediaController.
 *
 * Media management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MEDIA')]
#[Route('/admin-%security_token%/{website}/medias', schemes: '%protocol%')]
class MediaController extends AdminController
{
    /**
     * MediaController constructor.
     */
    public function __construct(
        protected MediaFormManagerInterface $mediaLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Library view.
     *
     * @throws InvalidArgumentException|MappingException|NonUniqueResultException|ReflectionException|QueryException
     */
    #[Route('/library/{folder}', name: 'admin_medias_library', defaults: ['folder' => null], methods: 'GET|POST')]
    public function library(Request $request, PaginatorInterface $paginator): JsonResponse|Response
    {
        $website = $this->getWebsite();
        $this->template = 'admin/page/media/library.html.twig';
        $this->getBaseArguments($request);

        $formPositions = $this->getTreeForm($request, Folder::class);
        if ($formPositions instanceof JsonResponse) {
            return $formPositions;
        }

        $historyRequestAsBool = isset($_GET['history']) && !preg_match('/\//', $_GET['history']) && in_array($_GET['history'], ['true', 'false', false, true, 0, 1]);
        $this->arguments['history'] = $historyRequestAsBool ? (bool) $_GET['history'] : $this->generateUrl('admin_medias_library', ['website' => $website->id, 'folder' => $request->get('folder')]);
        $this->arguments['formPositions'] = $formPositions->createView();
        $arguments = $this->editionArguments($request);
        $arguments['params']->medias = $paginator->paginate(
            $arguments['params']->medias,
            $this->coreLocator->request()->query->getInt('page', 1),
            24,
            ['wrap-queries' => true]
        );

        return $this->forward('App\Controller\Admin\AdminController::edition', $arguments);
    }

    /**
     * Edit media.
     *
     * @throws NonUniqueResultException|MappingException|InvalidArgumentException|ReflectionException|QueryException
     */
    #[Route('/edit/{media}', name: 'admin_media_edit', options: ['expose' => true], methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->class = Media::class;
        $this->formType = MediaType::class;
        $this->formManager = $this->mediaLocator->library();
        $this->formOptions = ['edition' => true, 'copyright' => true, 'name' => 'col-12', 'quality' => true, 'disabledTitle' => true];
        $this->template = 'admin/page/media/modal-media.html.twig';

        $website = $request->get('website');
        $media = $this->coreLocator->em()->getRepository($this->class)->find($request->get('media'));
        $this->arguments['redirection'] = $this->generateUrl('admin_medias_library', [
            'website' => $website,
            'folder' => $media->getFolder() instanceof Folder ? $media->getFolder()->getId() : null,
        ]);

        return $this->forward('App\Controller\Admin\AdminController::edition', $this->editionArguments($request));
    }

    /**
     * Media modal.
     */
    #[Route('/modal', name: 'admin_medias_modal', options: ['expose' => true], methods: 'GET|POST')]
    public function modal(Request $request, PaginatorInterface $paginator): JsonResponse
    {
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        $options = (array) json_decode($request->get('options'));
        $arguments = $this->getBaseArguments($request);
        $arguments['options'] = $options;
        $arguments['form'] = $form->createView();
        $arguments['history'] = false;
        $arguments['asCopy'] = $asCopy = isset($options['type']) && 'copy' === $options['type'];

        foreach ($options as $name => $value) {
            $arguments[$name] = $value;
        }

        if (!$asCopy && $form->isSubmitted() and $form->isValid()) {
            $arguments['medias'] = $this->mediaLocator->search()->search($form, $this->getWebsite()->entity);
        }

        $arguments['medias'] = $paginator->paginate(
            $arguments['medias'],
            $this->coreLocator->request()->query->getInt('page', 1),
            27,
            ['wrap-queries' => true]
        );

        return new JsonResponse(['html' => $this->renderView('admin/page/media/modal.html.twig', $arguments)]);
    }

    /**
     * Media modal to add.
     */
    #[Route('/modal/add/{media}', name: 'admin_medias_modal_add', options: ['expose' => true], methods: 'GET|POST')]
    public function modalAdd(Request $request, Media $media): JsonResponse
    {
        $this->mediaLocator->modalLibrary()->add($this->getWebsite()->entity, $media, $request->get('options'));

        return new JsonResponse(['success' => true]);
    }

    /**
     * Delete Media.
     *
     * {@inheritdoc}
     */
    #[IsGranted('ROLE_DELETE')]
    #[Route('/delete/{media}', name: 'admin_media_delete', options: ['expose' => true], methods: 'DELETE')]
    public function delete(Request $request): RedirectResponse|JsonResponse|Response
    {
        $this->class = Media::class;
        $media = $this->coreLocator->em()->getRepository(Media::class)->find($request->get('media'));
        if ($media instanceof Media && $media->getMedia() instanceof Media) {
            $parentMedia = $media->getMedia();
            $exist = false;
            foreach ($parentMedia->getMediaScreens() as $mediaScreen) {
                if ($mediaScreen->getFilename() && $mediaScreen->getId() !== intval($request->get('media'))) {
                    $exist = true;
                    break;
                }
            }
            $parentMedia->setHaveMediaScreens($exist);
            $this->coreLocator->em()->persist($parentMedia);
        }

        return parent::delete($request);
    }

    /**
     * Delete Media.
     *
     * @throws NonUniqueResultException
     */
    #[IsGranted('ROLE_DELETE')]
    #[Route('/remove/{media}', name: 'admin_media_remove', options: ['expose' => true], methods: 'DELETE')]
    public function remove(Request $request, Media $media): JsonResponse
    {
        $remove = $this->mediaLocator->media()->removeMedia($media);

        return new JsonResponse(['success' => $remove->success, 'message' => $remove->message]);
    }

    /**
     * Medias reorder list.
     */
    #[Route('/reorder-list', name: 'admin_medias_reorder', options: ['isMainRequest' => false], methods: 'GET|POST')]
    public function reorderMedias(Website $website): JsonResponse
    {
        $classnames = [
            NewscastEntities\Newscast::class => 'Actualités',
            NewscastEntities\Category::class => "Catégories d'actualités",
            CatalogEntities\Product::class => 'Produits',
            CatalogEntities\Category::class => 'Catégories de produits',
            Page::class => 'Pages',
        ];

        $data = [];
        $excluded = ['default-media', 'webmaster', 'pictogram', 'gdpr', 'map'];
        foreach ($classnames as $classname => $name) {
            $entities = $this->coreLocator->em()->getRepository($classname)->findBy(['website' => $website]);
            foreach ($entities as $entity) {
                foreach ($entity->getMediaRelations() as $mediaRelation) {
                    $media = $mediaRelation->getMedia();
                    $folder = $media ? $media->getFolder() : null;
                    if (!$folder && $media || ($media && $media->getFilename() && !in_array($folder->getSlug(), $excluded))) {
                        if ($mediaRelation->getMedia() instanceof Media) {
                            $data[] = ['website' => $website, 'classname' => $classname, 'entity' => $entity, 'media' => $media, 'name' => $name];
                            if (method_exists($entity, 'getLayout') && $entity->getLayout()) {
                                $blocks = $this->coreLocator->em()->getRepository(Block::class)->createQueryBuilder('b')
                                    ->leftJoin('b.mediaRelations', 'mr')
                                    ->leftJoin('mr.media', 'm')
                                    ->leftJoin('b.col', 'c')
                                    ->leftJoin('c.zone', 'z')
                                    ->leftJoin('z.layout', 'l')
                                    ->andWhere('m.filename IS NOT NULL')
                                    ->andWhere('l.id = :layout')
                                    ->setParameter('layout', $entity->getLayout())
                                    ->getQuery()
                                    ->getResult();
                                foreach ($blocks as $block) {
                                    foreach ($block->getMediaRelations() as $mediaRelation) {
                                        if ($mediaRelation->getMedia() instanceof Media) {
                                            $data[] = ['website' => $website, 'classname' => $classname, 'entity' => $entity, 'media' => $mediaRelation->getMedia(), 'name' => $name];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $folders = $this->coreLocator->em()->getRepository(Folder::class)->findBy(['website' => $website], ['level' => 'DESC', 'position' => 'DESC']);
        foreach ($folders as $key => $folder) {
            if (in_array($folder->getSlug(), $excluded)) {
                unset($folders[$key]);
            }
        }

        return new JsonResponse(['html' => $this->renderView('admin/page/media/reorder-medias.html.twig', [
            'website' => $website,
            'data' => $data,
            'folders' => $folders,
            'count' => count($data) + count($folders),
        ])]);
    }

    /**
     * Media reorder process.
     */
    #[Route('/reorder-reorder-process/{media}/{entityId}', name: 'admin_media_reorder_process', options: ['isMainRequest' => false], methods: 'GET|POST')]
    public function reorderMedia(
        Request $request,
        Website $website,
        Media $media,
        int $entityId,
    ): JsonResponse {
        $folderName = $request->get('folderName') ? urldecode($request->get('folderName')) : null;
        $classname = $request->get('classname') ? urldecode($request->get('classname')) : null;
        if ($folderName && $classname && $media->getFilename()) {
            $mediaFolder = $media->getFolder();
            $entity = $this->coreLocator->em()->getRepository($classname)->find($entityId);
            $parentFolder = $this->getFolder($website, $folderName);
            if ($entity instanceof CatalogEntities\Product) {
                $parentFolder = $this->getFolder($website, $entity->getCatalog()->getAdminName(), $parentFolder);
            } elseif ($entity instanceof NewscastEntities\Newscast && $entity->getCategory()) {
                $year = $entity->getPublicationDate() ? $entity->getPublicationDate()->format('Y')
                    : ($entity->getPublicationStart() ? $entity->getPublicationStart()->format('Y')
                        : ($entity->getUpdatedAt() ? $entity->getUpdatedAt()->format('Y')
                            : ($entity->getCreatedAt() ? $entity->getCreatedAt()->format('Y') : '')));
                $parentFolderName = $year.' Catégorie : '.$entity->getCategory()->getAdminName();
                $parentFolder = $this->getFolder($website, $parentFolderName, $parentFolder);
            }
            $folder = $this->getFolder($website, $entity->getAdminName(), $parentFolder);
            $reset = !$mediaFolder || ($mediaFolder->getSlug() !== $folder->getSlug())
                || ($mediaFolder->getParent() && $mediaFolder->getParent()->getSlug() !== $parentFolder->getSlug());
            $isMainMedia = $media->getFolder() && 'default-media' === $media->getFolder()->getSlug();
            if ($reset && !$isMainMedia) {
                $media->setFolder($folder);
                $this->coreLocator->em()->persist($media);
                $this->coreLocator->em()->flush();
            }
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Clear empty folder.
     */
    #[Route('/reorder-clear-empty-folder/{folder}', name: 'admin_media_clear_empty_folder', options: ['isMainRequest' => false], methods: 'GET|POST')]
    public function clearFolder(Website $website, Folder $folder): JsonResponse
    {
        $count = 0;
        foreach ($folder->getMedias() as $media) {
            if ($media->getFilename()) {
                ++$count;
            }
        }
        if (0 === $count) {
            $childrenFolders = $this->coreLocator->em()->getRepository(Folder::class)->findBy(['parent' => $folder->getId()]);
            foreach ($folder->getMedias() as $media) {
                if (!$media->getFilename()) {
                    $media->setFolder(null);
                    $this->coreLocator->em()->persist($folder);
                    $this->coreLocator->em()->flush();
                }
            }
            if (!$childrenFolders) {
                $levelFolders = $this->coreLocator->em()->getRepository(Folder::class)->findBy(['website' => $website, 'level' => $folder->getLevel(), 'parent' => $folder->getParent()]);
                foreach ($levelFolders as $levelFolder) {
                    if ($levelFolder->getPosition() > $folder->getPosition() && $levelFolder->getId() !== $folder->getId()) {
                        $levelFolder->setPosition($levelFolder->getPosition() - 1);
                        $this->coreLocator->em()->persist($levelFolder);
                    }
                }
                $this->coreLocator->em()->remove($folder);
                $this->coreLocator->em()->flush();
            }
        } elseif ('1' === $folder->getAdminName()) {
            $folder->setAdminName($folder->getSlug());
            $folder->setAdminName(Urlizer::urlize($folder->getSlug()));
            $this->coreLocator->em()->persist($folder);
            $this->coreLocator->em()->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * To get Folder.
     *
     * @throws Exception
     */
    private function getFolder(Website $website, string $name, ?Folder $parent = null): Folder
    {
        $slug = Urlizer::urlize($name);
        $level = $parent instanceof Folder ? $parent->getLevel() + 1 : 1;
        $repository = $this->coreLocator->em()->getRepository(Folder::class);
        $folders = $repository->findBy(['website' => $website, 'level' => $level, 'parent' => $parent]);
        $folder = $repository->findOneBy(['website' => $website, 'slug' => $slug, 'level' => $level, 'parent' => $parent]);

        if (!$folder) {
            $folder = new Folder();
            $folder->setAdminName($name);
            $folder->setSlug($slug);
            $folder->setWebsite($website);
            $folder->setPosition(count($folders) + 1);
            $folder->setParent($parent);
            $folder->setLevel($level);
            $folder->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $folder->setCreatedBy($this->getUser());
            $this->coreLocator->em()->persist($folder);
            $this->coreLocator->em()->flush();
        }

        if ($folder->getAdminName() !== $name) {
            $folder->setAdminName($name);
            $this->coreLocator->em()->persist($folder);
            $this->coreLocator->em()->flush();
        }

        return $folder;
    }

    /**
     * Get base arguments.
     */
    private function getBaseArguments(Request $request): array
    {
        $website = $this->getWebsite();

        $filesSizes = [];
        $filenames = [];
        $fileSizeLimit = '500k';
        $uploadDirname = $this->coreLocator->projectDir().'/public/uploads/'.$website->uploadDirname.'/';
        $uploadDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadDirname);
        $finder = Finder::create();
        $finder->in($uploadDirname)->files()->size('>= '.$fileSizeLimit)->depth([0]);
        $this->arguments['tooHeavyFilesSize'] = $this->arguments['filesSize'] = 0;
        foreach ($finder as $file) {
            $filenames[] = $file->getFilename();
            $filesSizes[$file->getFilename()] = $file->getSize();
            $this->arguments['tooHeavyFilesSize'] = $this->arguments['filesSize'] = $this->arguments['tooHeavyFilesSize'] + $file->getSize();
        }

        $folderRepository = $this->coreLocator->em()->getRepository(Folder::class);
        $folder = $request->get('folder') ? $folderRepository->findOneByWebsite($website, $request->attributes->getInt('folder')) : null;
        $folders = $folderRepository->findByWebsite($website);
        $this->arguments['folder'] = $folder;
        $this->arguments['tree'] = $this->getTree($folders);
        $this->arguments['tooHeavyFiles'] = count($filesSizes) > 0 ? $this->coreLocator->em()->getRepository(Media::class)->findTooHeavyFiles($website, $filenames, $filesSizes) : [];
        $this->arguments['fileSizeLimit'] = $fileSizeLimit;
        $this->arguments['tooHeavyFilesSizes'] = $filesSizes;
        $this->arguments['tooHeavyFilesCount'] = count($this->arguments['tooHeavyFiles']);
        $this->arguments['medias'] = $request->get('too_heavy_files') ? $this->arguments['tooHeavyFiles']
            : $this->coreLocator->em()->getRepository(Media::class)->findByWebsiteAndFolder($website, $folder);

        return $this->arguments;
    }
}
