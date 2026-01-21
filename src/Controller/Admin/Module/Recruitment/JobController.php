<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Recruitment;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Recruitment\Job;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Recruitment\JobType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * JobController
 *
 * Job Action management
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[IsGranted('ROLE_RECRUITMENT')]
#[Route('/admin-%security_token%/{website}/recruitments/jobs', schemes: '%protocol%')]
class JobController extends AdminController
{
	protected ?string $class = Job::class;
	protected ?string $formType = JobType::class;

    /**
     * SectorController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->job();
        $this->exportService = $adminLocator->exportManagers()->productsService();
        parent::__construct($coreLocator, $adminLocator);
    }

	/**
	 * Index Job
	 *
	 * {@inheritdoc}
	 */
	#[Route('/index', name: 'admin_recruitmentjob_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

	/**
	 * New Job
	 *
	 * {@inheritdoc}
	 */
	#[Route('/new', name: 'admin_recruitmentjob_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

	/**
	 * Edit Job
	 *
	 * {@inheritdoc}
	 */
	#[Route('/edit/{recruitmentjob}', name: 'admin_recruitmentjob_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

	/**
	 * Position Job
	 *
	 * {@inheritdoc}
	 */
	#[Route('/position/{recruitmentjob}', name: 'admin_recruitmentjob_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

	/**
	 * Delete Job
	 *
	 * {@inheritdoc}
	 */
	#[Route('/delete/{recruitmentjob}', name: 'admin_recruitmentjob_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}