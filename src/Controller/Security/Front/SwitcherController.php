<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use App\Controller\Front\FrontController;
use App\Entity\Security\UserFront;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SwitcherController.
 *
 * Users switcher management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SwitcherController extends FrontController
{
    /**
     * Users switcher.
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/secure/user/switcher', name: 'security_switcher', methods: 'GET', schemes: '%protocol%')]
    public function switcher(): Response
    {
        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/user-switcher.html.twig', array_merge([
            'templateName' => 'security-front',
            'security' => $website->security,
            'users' => $this->coreLocator->em()->getRepository(UserFront::class)->findAll(),
        ], $this->defaultArgs($website)));
    }
}
