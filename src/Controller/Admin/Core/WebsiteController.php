<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Form\Interface\ApiFormManagerInterface;
use App\Form\Interface\CoreFormManagerInterface;
use App\Form\Interface\InformationFormManagerInterface;
use App\Form\Type\Core\Website\WebsiteType;
use App\Form\Type\Core\WebsitesSelectorType;
use App\Repository\Core\WebsiteRepository;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * WebsiteController.
 *
 * WebsiteModel management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/admin-%security_token%/{website}/websites', schemes: '%protocol%')]
class WebsiteController extends AdminController
{
    protected ?string $class = Website::class;
    protected ?string $formType = WebsiteType::class;

    /**
     * WebsiteController constructor.
     */
    public function __construct(
        protected CoreFormManagerInterface $coreFormInterface,
        protected InformationFormManagerInterface $infoFormLocator,
        protected ApiFormManagerInterface $apiFormManagerLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $coreFormInterface->website();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index WebsiteModel.
     *
     * {@inheritdoc}
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/index', name: 'admin_site_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New WebsiteModel.
     *
     * {@inheritdoc}
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/new', name: 'admin_site_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit WebsiteModel.
     *
     * {@inheritdoc}
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/edit/{site}', name: 'admin_site_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $website = $this->getWebsite();
        $this->template = 'admin/page/website/website.html.twig';
        $this->infoFormLocator->networks()->synchronizeLocales($website->entity, $website->seoConfiguration);
        $this->apiFormManagerLocator->google()->synchronizeLocales($website->entity, $website->seoConfiguration);
        $this->apiFormManagerLocator->custom()->synchronizeLocales($website->entity, $website->seoConfiguration);

        return parent::edit($request);
    }

    /**
     * Show WebsiteModel.
     *
     * {@inheritdoc}
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/show/{site}', name: 'admin_site_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Websites selector.
     *
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/selector', name: 'admin_site_selector', methods: 'GET|POST')]
    public function websitesSelector(Request $request, WebsiteRepository $websiteRepository): RedirectResponse|Response
    {
        $websitesCount = count($websiteRepository->findAll());
        if (1 === $websitesCount) {
            return new Response();
        }

        /** @var User $user */
        $user = $this->getUser();
        $isInternalUser = in_array('ROLE_INTERNAL', $user->getRoles());
        $websites = $isInternalUser ? $websiteRepository->findAll() : $user->getWebsites();

        $form = $this->createForm(WebsitesSelectorType::class, null, ['website' => $this->getWebsite()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = (object) $form->getData();
            if (2 === intval($data->websites)) {
                return $this->redirectToRoute('admin_newscast_index', ['website' => $data->websites]);
            }

            return $this->redirectToRoute('admin_page_tree', ['website' => $data->websites]);
        }

        return $this->adminRender('admin/include/sidebar/include/websites-selector.html.twig', [
            'websites' => $websites,
            'form' => $form->createView(),
        ]);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('site')) {
            $items[$this->coreLocator->translator()->trans('Sites', [], 'admin_breadcrumb')] = 'admin_site_index';
        }

        parent::breadcrumb($request, $items);
    }
}
