<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Portfolio;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\BlockType;
use App\Entity\Module\Portfolio\Card;
use App\Form\Type\Module\Portfolio\CardType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CardController.
 *
 * Portfolio Card Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_PORTFOLIO')]
#[Route('/admin-%security_token%/{website}/portfolios/cards', schemes: '%protocol%')]
class CardController extends AdminController
{
    protected ?string $class = Card::class;
    protected ?string $formType = CardType::class;

    /**
     * Index Card.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_portfoliocard_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Card.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_portfoliocard_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Card.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{portfoliocard}', name: 'admin_portfoliocard_edit', methods: 'GET|POST')]
    #[Route('/layout/{portfoliocard}', name: 'admin_portfoliocard_layout', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->arguments['blockTypesDisabled'] = ['layout' => ['']];
        $this->arguments['blockTypesCategories'] = ['layout', 'content', 'global', 'action', 'modules'];
        $this->arguments['blockTypeAction'] = $this->coreLocator->em()->getRepository(BlockType::class)->findOneBy(['slug' => 'core-action']);

        return parent::edit($request);
    }

    /**
     * Show Card.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{portfoliocard}', name: 'admin_portfoliocard_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Card.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{portfoliocard}', name: 'admin_portfoliocard_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Card.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{portfoliocard}', name: 'admin_portfoliocard_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('portfoliocard')) {
            $items[$this->coreLocator->translator()->trans('Fiches', [], 'admin_breadcrumb')] = 'admin_portfoliocard_index';
        }

        parent::breadcrumb($request, $items);
    }
}
