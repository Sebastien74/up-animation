<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Recruitment;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Recruitment\Contract;
use App\Form\Type\Module\Recruitment\ContractType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;;

/**
 * ContractController
 *
 * Contract Action management
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[IsGranted('ROLE_RECRUITMENT')]
#[Route('/admin-%security_token%/{website}/recruitments/contracts', schemes: '%protocol%')]
class ContractController extends AdminController
{
	protected ?string $class = Contract::class;
	protected ?string $formType = ContractType::class;

	/**
	 * Index Contract
	 *
	 * {@inheritdoc}
	 */
	#[Route('/index', name: 'admin_recruitmentcontract_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

	/**
	 * New Contract
	 *
	 * {@inheritdoc}
	 */
	#[Route('/new', name: 'admin_recruitmentcontract_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

	/**
	 * Edit Contract
	 *
	 * {@inheritdoc}
	 */
	#[Route('/edit/{recruitmentcontract}', name: 'admin_recruitmentcontract_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

	/**
	 * Position Contract
	 *
	 * {@inheritdoc}
	 */
	#[Route('/position/{recruitmentcontract}', name: 'admin_recruitmentcontract_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

	/**
	 * Delete Contract
	 *
	 * {@inheritdoc}
	 */
	#[Route('/delete/{recruitmentcontract}', name: 'admin_recruitmentcontract_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}