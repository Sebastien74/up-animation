<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Layout;
use Doctrine\ORM\PersistentCollection;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(Request $request, PaginatorInterface $paginator)
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
    public function layout(Request $request)
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
        $layout = $this->coreLocator->em()->getRepository(Layout::class)->find($request->get('layout'));
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