<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Zone;
use App\Form\Interface\LayoutFormFormManagerLocator;
use App\Form\Type\Layout\Management as FormType;
use App\Repository\Layout\ZoneRepository;
use App\Service\Admin\LayoutServiceInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ZoneController.
 *
 * Layout Zone management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/layouts/zones', schemes: '%protocol%')]
class ZoneController extends AdminController
{
    protected ?string $class = Zone::class;
    protected ?string $formType = FormType\ZoneType::class;

    /**
     * PageController constructor.
     */
    public function __construct(
        protected LayoutFormFormManagerLocator $layoutLocator,
        protected \App\Service\Interface\CoreLocatorInterface $coreLocator,
        protected \App\Service\Interface\AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * New Zone.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/new/{layout}', name: 'admin_zone_new', methods: 'GET|POST')]
    public function add(Request $request, Layout $layout): JsonResponse
    {
        $form = $this->createForm(FormType\ZoneGridType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->layoutLocator->zone()->add($layout, $form);

            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['html' => $this->renderView('admin/core/layout/new-zone.html.twig', [
            'form' => $form->createView(),
            'layout' => $layout,
        ])]);
    }

    /**
     * Edit Zone.
     *
     * {@inheritdoc}
     */
    #[Route('/{layout}/{interfaceName}/{interfaceEntity}/edit/{zone}', name: 'admin_zone_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Edit background color Zone.
     */
    #[Route('/background/{zone}', name: 'admin_zone_background', methods: 'GET|POST')]
    public function background(Request $request)
    {
        $this->disableFlash = true;
        $this->template = 'admin/core/layout/background.html.twig';
        $this->formType = FormType\BackgroundColorZoneType::class;

        return parent::edit($request);
    }

    /**
     * Duplicate Zone.
     *
     * {@inheritdoc}
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    #[Route('/duplicate/{zone}', name: 'admin_zone_duplicate', methods: 'GET|POST')]
    public function duplicate(Request $request)
    {
        $this->formType = FormType\ZoneDuplicateType::class;
        $this->formDuplicateManager = $this->layoutLocator->zoneDuplicate();

        return parent::duplicate($request);
    }

    /**
     * Zone[] positions update.
     */
    #[Route('/positions/pack/{data}', name: 'admin_zones_positions', options: ['expose' => true], methods: 'POST')]
    public function positions(ZoneRepository $zoneRepository, string $data): JsonResponse
    {
        $zonesData = explode('&', $data);
        foreach ($zonesData as $zoneData) {
            $matches = explode('=', $zoneData);
            $zone = $zoneRepository->find($matches[0]);
            $zone->setPosition(intval($matches[1]));
            $this->coreLocator->em()->persist($zone);
        }
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Size Zone.
     */
    #[Route('/size/{zone}', name: 'admin_zone_size', options: ['expose' => true], methods: 'GET')]
    public function size(Request $request, Zone $zone): JsonResponse
    {
        $zone->setFullSize((bool) $request->get('size'));
        $this->coreLocator->em()->persist($zone);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Standardize Col[] width in Zone.
     */
    #[Route('/standardize-elements/{zone}', name: 'admin_cols_standardize', options: ['expose' => true], methods: 'GET')]
    public function standardizeElements(Request $request, Zone $zone): JsonResponse
    {
        $zone->setStandardizeElements($request->get('standardize'));
        $this->coreLocator->em()->persist($zone);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Edit Zone configuration.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|ReflectionException|ContainerExceptionInterface|QueryException
     */
    #[Route('/modal/configuration/{zone}', name: 'admin_zone_configuration', methods: 'GET|POST')]
    public function configuration(Request $request)
    {
        $this->disableFlash = true;
        $this->entity = $this->coreLocator->em()->getRepository(Zone::class)->find($request->get('zone'));
        $this->formType = FormType\ZoneConfigurationType::class;
        $this->formManager = $this->layoutLocator->zoneConfiguration();
        $this->template = 'admin/core/layout/zone-configuration.html.twig';
        $this->arguments['zone'] = $this->entity;

        return parent::edit($request);
    }

    /**
     * Delete Zone.
     *
     * {@inheritdoc}
     */
    #[Route('/{layout}/delete/{zone}', name: 'admin_zone_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To reset Zone margins.
     */
    #[Route('/reset-margins/{zone}', name: 'admin_zone_reset_margins', methods: 'DELETE')]
    public function resetMargins(LayoutServiceInterface $service, Zone $zone): JsonResponse
    {
        return $service->resetMargins($zone);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('layout')) {
            $items[$this->coreLocator->translator()->trans('Arborescence', [], 'admin_'.$request->get('interfaceName').'_tree')] = 'admin_'.$request->get('interfaceName').'_tree';
            $items[$this->coreLocator->translator()->trans('Mise en page', [], 'admin_'.$request->get('interfaceName').'_layout')] = 'admin_'.$request->get('interfaceName').'_layout';
        }

        parent::breadcrumb($request, $items);
    }
}
