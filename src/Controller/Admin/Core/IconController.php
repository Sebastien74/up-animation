<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Icon;
use App\Entity\Core\Website;
use App\Form\Interface\CoreFormManagerInterface;
use App\Form\Type\Core\IconType;
use App\Repository\Core\IconRepository;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * IconController.
 *
 * Icon management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/development/icons', schemes: '%protocol%')]
class IconController extends AdminController
{
    /**
     * IconController constructor.
     */
    public function __construct(
        protected CoreFormManagerInterface $coreFormInterface,
        protected \App\Service\Interface\CoreLocatorInterface $coreLocator,
        protected \App\Service\Interface\AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Icon library.
     */
    #[Route('/library/{library}', name: 'admin_icons', methods: 'GET|POST')]
    public function library(Request $request, IconRepository $iconRepository, string $projectDir, Website $website, string $library): Response
    {
        $icons = $iconRepository->findBy(['configuration' => $website->getConfiguration()]);
        $websiteIcons = [];
        foreach ($icons as $icon) {
            $websiteIcons[] = $icon->getPath();
        }
        parent::breadcrumb($request, []);

        return $this->adminRender('admin/page/core/icons.html.twig', array_merge($this->arguments, [
            'websiteIcons' => $websiteIcons,
            'library' => $library,
            'libraries' => $this->getLibraries($icons, $projectDir),
        ]));
    }

    /**
     * Add Icon[].
     */
    #[Route('/icons-add', name: 'admin_icons_add', methods: 'GET|POST')]
    public function iconsAdd(Request $request, Website $website): JsonResponse|Response
    {
        $form = $this->createForm(IconType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            return $this->coreFormInterface->icon()->execute($website, $form);
        }

        return $this->render('admin/page/core/icons-add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Add Icon.
     */
    #[Route('/add', name: 'admin_icon_add', options: ['expose' => true], methods: 'GET')]
    public function add(Request $request, IconRepository $iconRepository, Website $website): JsonResponse
    {
        $path = json_decode(urldecode($request->get('path')));
        $existing = $iconRepository->findBy([
            'configuration' => $website->getConfiguration(),
            'path' => $path,
        ]);

        if (!$existing) {
            $matches = explode('/', $path);
            $filename = end($matches);
            $this->coreFormInterface->icon()->addIcon($filename, $path, $website->getConfiguration());
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Remove Icon.
     */
    #[Route('/remove', name: 'admin_icon_remove', options: ['expose' => true], methods: 'GET')]
    public function remove(Request $request, Website $website): JsonResponse
    {
        $path = json_decode(urldecode($request->get('path')));
        $this->coreFormInterface->icon()->remove($path, $website->getConfiguration());

        return new JsonResponse(['success' => true]);
    }

    /**
     * Get Libraries.
     */
    private function getLibraries(array $icons, string $projectDir): array
    {
        $libraries = [];
        $dirname = $projectDir.'/public/medias/icons';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $finder = Finder::create();
        $finder->in($dirname);

        foreach ($finder as $file) {
            $projectDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $projectDir);
            $realPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file->getRealPath());
            $realRelativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file->getRelativePath());
            $dirname = str_replace($projectDirname.DIRECTORY_SEPARATOR.'public', '', $realPath);
            $params = ['src' => str_replace(DIRECTORY_SEPARATOR, '/', $dirname), 'filename' => $file->getFilename()];
            if ($file->getExtension() && preg_match('/\\'.DIRECTORY_SEPARATOR.'/', $realRelativePath)) {
                $matches = explode(DIRECTORY_SEPARATOR, $realRelativePath);
                $libraries[$matches[0]][] = $params;
            } elseif ($file->getExtension()) {
                if ('flags' === $file->getRelativePath()) {
                    $matchesLocale = explode('.', $file->getFilename());
                    $params['locale'] = $matchesLocale[0];
                }
                $libraries[$file->getRelativePath()][] = $params;
            }
        }

        foreach ($icons as $icon) {
            /* @var Icon $icon */
            $libraries['website'][] = ['src' => $icon->getPath(), 'filename' => $icon->getFilename(), 'locale' => $icon->getLocale()];
        }

        return $libraries;
    }
}
