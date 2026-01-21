<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Service\DataFixtures\SecurityFixtures;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TestController.
 *
 * Development tester
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/tester', schemes: '%protocol%')]
class TestController extends AdminController
{
    public function __construct(
        \App\Service\Interface\CoreLocatorInterface $coreLocator,
        \App\Service\Interface\AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Test view.
     *
     * @throws \Exception
     */
    #[Route('/view/{website}', name: 'admin_dev_test', methods: 'GET|POST')]
    public function test(Request $request, SecurityFixtures $fixtures, Website $website): Response
    {
        $fixtures->addMessages($website);

        die;

        parent::breadcrumb($request, []);

        return $this->render('admin/page/development/test.html.twig', array_merge($this->arguments, [
        ]));
    }
}
