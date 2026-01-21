<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use App\Form\Type\Development\FileUrlizerType;
use App\Service\Development\FileUrlizerService;
use App\Twig\Core\AppRuntime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ToolController.
 *
 * Webmaster tools
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development', schemes: '%protocol%')]
class ToolController extends AdminController
{
    /**
     * Tools view.
     *
     * @throws \Exception
     */
    #[Route('/tools', name: 'admin_tools', methods: 'GET')]
    public function tools(Request $request): Response
    {
        parent::breadcrumb($request, []);
        return $this->adminRender('admin/page/development/tools.html.twig', $this->arguments);
    }

    /**
     * Phpinfo view.
     */
    #[Route('/phpinfo', name: 'admin_phpinfo', methods: 'GET')]
    public function phpinfo(Request $request, AppRuntime $appRuntime): Response
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_clean();
        $phpinfo = $appRuntime->removeBetween($phpinfo, ['style']);
        parent::breadcrumb($request, []);

        return $this->adminRender('admin/page/development/phpinfo.html.twig', array_merge($this->arguments, [
            'phpinfo' => $phpinfo,
        ]));
    }

    /**
     * Project info.
     */
    #[Route('/info/{website}', name: 'admin_project_info', methods: 'GET')]
    public function projectSizes(Request $request): Response
    {
        $disabled = ['node_modules'];
        $directoriesSizes = [];
        $subDirectoriesSizes = [];
        $files = [];
        $totalSize = 0;
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->coreLocator->projectDir());
        $finder = Finder::create();
        $finder->in($dirname)->directories()->depth([0]);
        foreach ($finder as $directory) {
            $pathname = $directory->getRelativePathname();
            if (!in_array($pathname, $disabled)) {
                $finderFiles = Finder::create();
                $finderFiles->in($directory->getRealPath());
                foreach ($finderFiles as $file) {
                    $size = !empty($directoriesSizes[$pathname]) ? $directoriesSizes[$pathname] : 0;
                    $directoriesSizes[$pathname] = $size + $file->getSize();
                    $totalSize = $totalSize + $file->getSize();
                    $files[$file->getRealPath()] = $file->getSize();
                    if ($file->isDir() && $directory->getRealPath() === $file->getPath()) {
                        $finderSubFiles = Finder::create();
                        $finderSubFiles->in($file->getRealPath())->files();
                        $subPathname = $file->getRelativePathname();
                        foreach ($finderSubFiles as $subFile) {
                            $size = !empty($subDirectoriesSizes[$pathname][$subPathname]) ? $subDirectoriesSizes[$pathname][$subPathname] : 0;
                            $subDirectoriesSizes[$pathname][$subPathname] = $size + $subFile->getSize();
                        }
                    }
                }
            }
        }

        parent::breadcrumb($request, []);

        return $this->adminRender('admin/page/development/project-info.html.twig', array_merge($this->arguments, [
            'directoriesSizes' => $directoriesSizes,
            'subDirectoriesSizes' => $subDirectoriesSizes,
            'website' => $this->coreLocator->website(),
            'totalSize' => $totalSize,
            'files' => $files,
            'filesCount' => count($files),
        ]));
    }

    /**
     * Tools view.
     */
    #[Route('/clear-sessions', name: 'admin_clear_sessions', methods: 'GET')]
    public function clearSession(Request $request): RedirectResponse
    {
        $matches = ['_route', 'last_', '_csrf', '_security', '_locale'];
        $session = $request->getSession();
        foreach ($session->all() as $name => $sessionRequest) {
            $remove = true;
            foreach ($matches as $match) {
                if (preg_match('/'.$match.'/', $name)) {
                    $remove = false;
                }
            }
            if ($remove) {
                $session->remove($name);
            }
        }

        return $this->redirect($request->headers->get('referer').'?clear=true');
    }

    /**
     * To rename files.
     */
    #[Route('/files-rename', name: 'admin_file_rename_tool', methods: 'GET|POST')]
    public function fileRename(Request $request, FileUrlizerService $fileUrlizer, string $projectDir): Response
    {
        $form = $this->createForm(FileUrlizerType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !empty($form->getData()['files'])) {
            $files = $form->getData()['files'];
            $zipName = $fileUrlizer->execute($files);

            $response = new Response(file_get_contents($zipName));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="'.$zipName.'"');
            $response->headers->set('Content-length', filesize($zipName));

            @unlink($zipName);

            $tmpDirname = $projectDir.'/public/uploads/tmp/rename/';
            $tmpDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tmpDirname);
            $filesystem = new Filesystem();
            $filesystem->remove($tmpDirname);

            return $response;
        }

        return $this->render('admin/page/development/files-urlizer.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
