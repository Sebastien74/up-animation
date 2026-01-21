<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Newsletter;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Newsletter\Campaign;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Newsletter\CampaignType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CampaignController.
 *
 * Newsletters Campaign Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_NEWSLETTER')]
#[Route('/admin-%security_token%/{website}/newsletters/campaigns', schemes: '%protocol%')]
class CampaignController extends AdminController
{
    protected ?string $class = Campaign::class;
    protected ?string $formType = CampaignType::class;

    /**
     * CampaignController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->newsletterCampaign();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Campaign.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_campaign_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $this->formManager->removeExpiredToken();

        return parent::index($request, $paginator);
    }

    /**
     * New Campaign.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_campaign_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Campaign.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{campaign}', name: 'admin_campaign_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Campaign.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{campaign}', name: 'admin_campaign_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Campaign.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{campaign}', name: 'admin_campaign_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Campaign.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{campaign}', name: 'admin_campaign_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('campaign')) {
            $items[$this->coreLocator->translator()->trans('Campagnes', [], 'admin_breadcrumb')] = 'admin_campaign_index';
        }

        parent::breadcrumb($request, $items);
    }
}
