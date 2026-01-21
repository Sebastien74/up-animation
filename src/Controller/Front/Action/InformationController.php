<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use Symfony\Component\HttpFoundation\Response;

/**
 * InformationController.
 *
 * Front contact information render
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InformationController extends FrontController
{
    /**
     * View.
     *
     * @throws \Exception
     */
    public function view(?Block $block = null): Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $template = $configuration->template;
        $information = $website->information;
        $entity = $block instanceof Block ? $block : $information;
        $entity->setUpdatedAt($information->entity->getUpdatedAt());

        return $this->render('front/'.$template.'/actions/information/view.html.twig', [
            'websiteTemplate' => $template,
            'website' => $website,
            'block' => $block,
            'information' => $information,
        ]);
    }
}
