<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * BooleanSwitcherController.
 *
 * Boolean management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}', schemes: '%protocol%')]
class BooleanSwitcherController extends AdminController
{
    /**
     * To switch boolean.
     *
     * @throws Exception
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/switch-boolean/{entityId}/{property}', name: 'admin_switch_boolean', methods: 'GET|POST')]
    public function switchBoolean(Request $request, Website $website, int $entityId, string $property): JsonResponse
    {
        $status = 'true' === $request->get('status');
        $repository = $this->coreLocator->em()->getRepository(urldecode($request->get('classname')));
        $currentEntity = $repository->find($entityId);
        $setter = 'set'.ucfirst($property);
        $uniqProperties = ['asDefault', 'main'];
        $interface = $this->getInterface(get_class($currentEntity));
        $masterFieldGetter = !empty($interface['masterField']) && is_object($currentEntity) ? 'get'.ucfirst($interface['masterField']) : null;

        if ($masterFieldGetter && method_exists($currentEntity, $masterFieldGetter) && !$currentEntity instanceof Website) {
            $masterEntity = $currentEntity->$masterFieldGetter();
            if (is_object($masterEntity) && method_exists($masterEntity, 'setUpdatedAt')) {
                $masterEntity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $this->coreLocator->em()->persist($masterEntity);
            }
        }

        if (method_exists($currentEntity, $setter)) {
            if ($status && in_array($property, $uniqProperties) && method_exists($currentEntity, 'getWebsite')) {
                $entities = $repository->findBy(['website' => $website]);
                foreach ($entities as $entity) {
                    $entity->$setter(false);
                    $this->coreLocator->em()->persist($entity);
                }
            }

            if ($currentEntity instanceof Website) {
                $configuration = $currentEntity->getConfiguration();
                $configuration->setOnlineStatus($status);
                $this->coreLocator->em()->persist($configuration);
            }

            $currentEntity->$setter($status);

            $this->coreLocator->em()->persist($currentEntity);
            $this->coreLocator->em()->flush();
        }

        return new JsonResponse(['success' => true, 'reload' => in_array($property, $uniqProperties)]);
    }
}
