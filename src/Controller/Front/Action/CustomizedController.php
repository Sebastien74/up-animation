<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use Symfony\Component\HttpFoundation\Response;
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
    #[Route('/contact-us', name: 'front_contact_us', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function zoneContactUs(): Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/customized/contact-us.html.twig', [
            'configuration' => $configuration,
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
        ]);
    }
}
