<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * PhpInfoController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PhpInfoController extends AbstractController
{
    /**
     * PhpInfoController constructor.
     */
    public function __construct(protected CoreLocatorInterface $coreLocator)
    {
    }

    #[Route('/phpinfo', priority: 1)]
    public function index(): Response
    {
        if (!$this->coreLocator->checkIP()) {
            throw new AccessDeniedHttpException('Access denied !!');
        }

        ob_start();
        phpinfo();
        $phpinfo = ob_get_clean();

        return $this->render('core/phpinfo.html.twig', [
            'phpinfo' => $phpinfo,
        ]);
    }
}
