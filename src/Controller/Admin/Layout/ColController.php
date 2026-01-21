<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Col;
use App\Entity\Layout\Zone;
use App\Form\Interface\LayoutFormFormManagerLocator;
use App\Form\Type\Layout\Management\BackgroundColorColType;
use App\Form\Type\Layout\Management\ColConfigurationType;
use App\Form\Type\Layout\Management\ColSizeType;
use App\Form\Type\Layout\Management\ColType;
use App\Repository\Layout\ColRepository;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ColController.
 *
 * Layout Col management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/layouts/zones/cols', schemes: '%protocol%')]
class ColController extends AdminController
{
    protected ?string $class = Col::class;
    protected ?string $formType = ColType::class;

    /**
     * ColController constructor.
     *
     * @throws ContainerExceptionInterface
     */
    public function __construct(
        protected LayoutFormFormManagerLocator $layoutLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $layoutLocator->colConfiguration();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * New Col.
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/new/{zone}', name: 'admin_col_new', methods: 'GET|POST')]
    public function add(Request $request, Zone $zone)
    {
        $col = new Col();
        $form = $this->createForm(ColSizeType::class, $col);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $position = count($zone->getCols()) + 1;
            $col->setZone($zone);
            $col->setPosition($position);
            $this->coreLocator->em()->persist($col);
            $this->coreLocator->em()->flush();
            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['html' => $this->renderView('admin/core/layout/new-col.html.twig', [
            'form' => $form->createView(),
            'zone' => $zone,
        ])]);
    }

    /**
     * Edit Col.
     *
     * {@inheritdoc}
     */
    #[Route('/{zone}/{interfaceName}/{interfaceEntity}/edit/{col}', name: 'admin_col_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Edit background color Col.
     */
    #[Route('/background/{col}', name: 'admin_col_background', options: ['expose' => true], methods: 'GET|POST')]
    public function background(Request $request)
    {
        $this->disableFlash = true;
        $this->template = 'admin/core/layout/background.html.twig';
        $this->formType = BackgroundColorColType::class;

        return parent::edit($request);
    }

    /**
     * Set Col size.
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/size/{col}/{size}', name: 'admin_col_size', options: ['expose' => true], methods: 'GET')]
    public function size(Col $col, int $size): JsonResponse
    {
        $col->setSize($size);
        $this->coreLocator->em()->persist($col);
        $this->coreLocator->em()->flush();
        $this->adminLocator->layoutManager()->setGridZone($col->getZone()->getLayout());

        return new JsonResponse(['success' => true]);
    }

    /**
     * Standardize Block[] width in Col.
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/standardize-elements/{col}', name: 'admin_blocks_standardize', options: ['expose' => true], methods: 'GET')]
    public function standardizeElements(Request $request, Col $col): JsonResponse
    {
        $col->setStandardizeElements($request->get('standardize'));
        $this->coreLocator->em()->persist($col);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Col[] positions update.
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/positions/pack/{data}', name: 'admin_cols_positions', options: ['expose' => true], methods: 'POST')]
    public function positions(ColRepository $colRepository, string $data): JsonResponse
    {
        $colsData = explode('&', $data);
        foreach ($colsData as $colData) {
            $matches = explode('=', $colData);
            $col = $colRepository->find($matches[0]);
            $col->setPosition(intval($matches[1]));
            $this->coreLocator->em()->persist($col);
        }
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Edit Col configuration.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|ReflectionException
     */
    #[Route('/modal/configuration/{col}', name: 'admin_col_configuration', methods: 'GET|POST')]
    public function configuration(Request $request)
    {
        $this->disableFlash = true;
        $this->entity = $this->coreLocator->em()->getRepository(Col::class)->find($request->get('col'));
        $this->formType = ColConfigurationType::class;
        $this->template = 'admin/core/layout/col-configuration.html.twig';
        $this->arguments['col'] = $this->entity;

        return parent::edit($request);
    }

    /**
     * Delete Col.
     *
     * {@inheritdoc}
     */
    #[Route('/{zone}/delete/{col}', name: 'admin_col_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}