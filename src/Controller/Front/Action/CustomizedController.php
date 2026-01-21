<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CustomizedController.
 *
 * Customized renders or actions
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/front/customized/action', schemes: '%protocol%')]
class CustomizedController extends FrontController
{
}
