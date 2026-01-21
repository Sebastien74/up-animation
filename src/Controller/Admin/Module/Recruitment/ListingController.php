<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Recruitment;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Recruitment\Listing;
use App\Form\Type\Module\Recruitment\ListingType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;;

/**
 * ListingController
 *
 * Recruitment Listing Action management
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[IsGranted('ROLE_RECRUITMENT')]
#[Route('/admin-%security_token%/{website}/recruitments/listings', schemes: '%protocol%')]
class ListingController extends AdminController
{
	protected ?string $class = Listing::class;
	protected ?string $formType = ListingType::class;

	/**
	 * Index Listing
	 *
	 * {@inheritdoc}
	 */
	#[Route('/index', name: 'admin_recruitmentlisting_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

	/**
	 * New Listing
	 *
	 * {@inheritdoc}
	 */
	#[Route('/new', name: 'admin_recruitmentlisting_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

	/**
	 * Edit Listing
	 *
	 * {@inheritdoc}
	 */
	#[Route('/edit/{recruitmentlisting}', name: 'admin_recruitmentlisting_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

	/**
	 * Show Listing
	 *
	 * {@inheritdoc}
	 */
	#[Route('/show/{recruitmentlisting}', name: 'admin_recruitmentlisting_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

	/**
	 * Position Listing
	 *
	 * {@inheritdoc}
	 */
	#[Route('/position/{recruitmentlisting}', name: 'admin_recruitmentlisting_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

	/**
	 * Delete Listing
	 *
	 * {@inheritdoc}
	 */
	#[Route('/delete/{recruitmentlisting}', name: 'admin_recruitmentlisting_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}