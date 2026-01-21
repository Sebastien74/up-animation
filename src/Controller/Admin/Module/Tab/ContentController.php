<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Tab;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Tab\Content;
use App\Entity\Module\Tab\Tab;
use App\Form\Type\Module\Tab\ContentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ContentController.
 *
 * Tab Content Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_TAB')]
#[Route('/admin-%security_token%/{website}/tabs/contents', schemes: '%protocol%')]
class ContentController extends AdminController
{
    protected ?string $class = Content::class;
    protected ?string $formType = ContentType::class;

    /**
     * Contents tree.
     *
     * {@inheritdoc}
     */
    #[Route('/{tab}/tree', name: 'admin_tabcontent_tree', methods: 'GET|POST')]
    public function tree(Request $request)
    {
        $tab = $this->coreLocator->em()->getRepository(Tab::class)->find($request->attributes->getInt('tab'));
        if ($tab instanceof Tab && 'accordion' === $tab->getTemplate()) {
            $this->arguments['forceLimit'] = 1;
        }

        return parent::tree($request);
    }

    /**
     * New Content.
     *
     * {@inheritdoc}
     */
    #[Route('/{tab}/new', name: 'admin_tabcontent_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Content.
     *
     * {@inheritdoc}
     */
    #[Route('/{tab}/edit/{tabcontent}', name: 'admin_tabcontent_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Content.
     *
     * {@inheritdoc}
     */
    #[Route('/{tab}/show/{tabcontent}', name: 'admin_tabcontent_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Delete Content.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{tabcontent}', name: 'admin_tabcontent_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('tab')) {
            $items[$this->coreLocator->translator()->trans("Groupes d'onglets", [], 'admin_breadcrumb')] = 'admin_tab_index';
            if ($request->get('tabcontent')) {
                $items[$this->coreLocator->translator()->trans('Contents', [], 'admin_breadcrumb')] = 'admin_tabcontent_tree';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
