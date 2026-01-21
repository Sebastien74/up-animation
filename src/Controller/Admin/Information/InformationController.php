<?php

declare(strict_types=1);

namespace App\Controller\Admin\Information;

use App\Controller\Admin\AdminController;
use App\Entity\Information\Information;
use App\Form\Interface\InformationFormManagerInterface;
use App\Form\Type\Information\InformationType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * InformationController.
 *
 * Information management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INFORMATION')]
#[Route('/admin-%security_token%/{website}/information', schemes: '%protocol%')]
class InformationController extends AdminController
{
    protected ?string $class = Information::class;
    protected ?string $formType = InformationType::class;

    /**
     * InformationController constructor.
     */
    public function __construct(
        protected InformationFormManagerInterface $infoFormLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $infoFormLocator->information();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Edit Information.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{information}/{tab}', name: 'admin_information_edit', defaults: ['tab' => null], methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->template = 'admin/page/information/information.html.twig';

        return parent::edit($request);
    }
}
