<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * PhpInfoController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PhpInfoController extends AbstractController
{
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        #[Autowire(param: 'kernel.environment')] private readonly string $kernelEnvironment,
    ) {
    }

    #[Route('/phpinfo', priority: 1)]
    #[IsGranted('ROLE_INTERNAL')]
    public function index(): Response
    {
        if ('prod' === $this->kernelEnvironment) {
            throw new NotFoundHttpException();
        }

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
