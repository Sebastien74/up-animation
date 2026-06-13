<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Layout\Layout;
use App\Entity\Seo\Url;
use App\Service\Admin\LayoutServiceInterface;
use App\Service\Admin\PageAnalyzerInterface;
use Doctrine\ORM\PersistentCollection;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * LayoutController.
 *
 * Layout management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/layouts', schemes: '%protocol%')]
class LayoutController extends AdminController
{
    protected ?string $class = Layout::class;

    /**
     * Index Layout.
     *
     * {@inheritdoc}
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/index', name: 'admin_layout_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator, ?string $domains = null): JsonResponse|string|Response
    {
        return parent::index($request, $paginator);
    }

    /**
     * Delete Layout.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{layout}', name: 'admin_layout_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * Layout.
     *
     * {@inheritdoc}
     */
    #[Route('/layout/{layout}', name: 'admin_layout_layout', methods: 'GET')]
    public function layout(Request $request): JsonResponse|string|Response
    {
        $mappedEntityInfos = $this->getMappedEntityInfos($request);
        if (!$mappedEntityInfos) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("L'entité de cette mise en page a été supprimé.", [], 'front'));
        }
        return $this->redirectToRoute('admin_'.$mappedEntityInfos->interface['name'].'_layout', [
            'website' => $this->getWebsite()->id,
            $mappedEntityInfos->interface['name'] => $mappedEntityInfos->entity->getId(),
        ]);
    }

    /**
     * Standardize all the layout margins: copy desktop margins/paddings to the other
     * screens for every zone, col and block of the layout.
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/standardize-margins/{layout}', name: 'admin_layout_standardize_margins', methods: 'DELETE')]
    public function standardizeMargins(LayoutServiceInterface $service, Layout $layout): JsonResponse
    {
        $this->denyUnlessEntityWebsite($layout);

        return $service->standardizeLayoutMargins($layout);
    }

    /**
     * Analyze the front rendering of the page: renders it internally (preview mode),
     * parses the HTML and returns a performance/rendering report for an admin modal.
     *
     * Admin/preview only: rendered with preview=true and gated by ROLE_ADMIN, it never
     * runs during public front navigation.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/analyze/{url}', name: 'admin_layout_analyze', methods: 'GET')]
    public function analyze(Request $request, Website $website, Url $url, PageAnalyzerInterface $analyzer): JsonResponse
    {
        $request->setLocale($url->getLocale());

        try {
            $start = microtime(true);
            $response = $this->forward('App\Controller\Front\IndexController::view', [
                'url' => $url->getCode(),
                'website' => $website,
                'preview' => true,
            ]);
            $report = $analyzer->analyze((string) $response->getContent(), $url->getCode());
            $report['meta']['renderMs'] = (int) round((microtime(true) - $start) * 1000);
        } catch (\Throwable $e) {
            $report = [
                'meta' => ['urlCode' => $url->getCode(), 'bytes' => 0, 'kb' => 0, 'dom' => 0, 'images' => 0, 'scripts' => 0, 'requests' => 0],
                'score' => null,
                'summary' => ['high' => 1, 'medium' => 0, 'low' => 0],
                'groups' => [[
                    'id' => 'error',
                    'label' => 'Erreur',
                    'counts' => ['high' => 1, 'medium' => 0, 'low' => 0],
                    'findings' => [[
                        'id' => 'error',
                        'severity' => 'high',
                        'label' => 'Analyse impossible',
                        'value' => 'Erreur de rendu',
                        'reco' => $this->coreLocator->isDebug() ? $e->getMessage() : "La page n'a pas pu être rendue pour analyse.",
                    ]],
                ]],
            ];
        }

        $previewUrl = $this->coreLocator->router()->generate('front_page_preview', [
            'website' => $website->getId(),
            'url' => $url->getId(),
        ]);
        $analyzeUrl = $this->coreLocator->router()->generate('admin_layout_analyze', [
            'website' => $website->getId(),
            'url' => $url->getId(),
        ]);

        return new JsonResponse(['html' => $this->renderView('admin/core/layout/page-analysis.html.twig', [
            'report' => $report,
            'url' => $url,
            'previewUrl' => $previewUrl,
            'analyzeUrl' => $analyzeUrl,
        ])]);
    }

    /**
     * Reset Layout.
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/reset/{layout}', name: 'admin_layout_reset', methods: 'GET')]
    public function reset(Request $request): JsonResponse
    {
        $mappedEntityInfos = $this->getMappedEntityInfos($request);
        $setter = 'set'.ucfirst($mappedEntityInfos->interface['name']);
        $mappedEntity = $mappedEntityInfos->entity;

        /** @var Layout $layout */
        $layout = $mappedEntityInfos->layout;

        $newLayout = new Layout();
        $newLayout->setWebsite($this->getWebsite());
        $newLayout->setAdminName($layout->getAdminName());
        $newLayout->setPosition($layout->getPosition());
        $newLayout->$setter($mappedEntity);

        $mappedEntity->setLayout($newLayout);

        $this->coreLocator->em()->persist($newLayout);
        $this->coreLocator->em()->persist($mappedEntity);

        $this->coreLocator->em()->remove($layout);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Get Layout mapped entity.
     */
    private function getMappedEntityInfos(Request $request): ?object
    {
        $excludes = ['createdBy', 'updatedBy', 'zones', 'website'];
        $layout = $this->coreLocator->em()->getRepository(Layout::class)->find($request->attributes->get('layout'));
        $associationsMapping = $this->coreLocator->em()->getClassMetadata(Layout::class)->getAssociationMappings();

        foreach ($associationsMapping as $property => $properties) {
            $getMethod = 'get'.ucfirst($property);
            $isMethod = 'is'.ucfirst($property);
            $existing = method_exists($layout, $getMethod) || method_exists($layout, $isMethod);
            if ($existing) {
                $mappedEntity = method_exists($layout, $getMethod) ? $layout->$getMethod() : $layout->$isMethod();
                if (!in_array($property, $excludes) && !empty($mappedEntity)) {
                    $classname = null;
                    $entity = $mappedEntity;
                    if ($mappedEntity instanceof PersistentCollection and !$mappedEntity->isEmpty()) {
                        $classname = $this->coreLocator->em()->getClassMetadata(get_class($mappedEntity[0]))->getName();
                        $entity = $mappedEntity[0];
                    } elseif (!$mappedEntity instanceof PersistentCollection) {
                        $classname = $this->coreLocator->em()->getClassMetadata(get_class($mappedEntity))->getName();
                    }

                    return (object) [
                        'layout' => $layout,
                        'entity' => $entity,
                        'interface' => $this->getInterface($classname),
                    ];
                }
            }
        }

        return null;
    }
}