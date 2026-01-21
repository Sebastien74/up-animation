<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Form\Interface\CoreFormManagerInterface;
use App\Form\Type\Core\SupportType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SupportController.
 *
 * Send email to support
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/support', schemes: '%protocol%')]
class SupportController extends AdminController
{
    /**
     * SupportController constructor.
     */
    public function __construct(
        protected CoreFormManagerInterface $coreFormInterface,
        protected \App\Service\Interface\CoreLocatorInterface $coreLocator,
        protected \App\Service\Interface\AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Contact.
     *
     * @throws \Exception
     */
    #[Route('/contact', name: 'admin_support', methods: 'GET|POST')]
    public function contact(Request $request, Website $website): RedirectResponse|Response
    {
        $form = $this->createForm(SupportType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->coreFormInterface->support()->send($form->getData(), $website);

            return $this->redirect($request->headers->get('referer'));
        }

        parent::breadcrumb($request, []);

        return $this->render('admin/page/core/support.html.twig', array_merge($this->arguments, [
            'form' => $form->createView(),
        ]));
    }
}
