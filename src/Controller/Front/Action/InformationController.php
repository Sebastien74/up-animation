<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Module\Catalog\Catalog;
use App\Model\Module\CatalogModel;
use Psr\Cache\InvalidArgumentException;
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
     * @throws \Exception|InvalidArgumentException
     */
    public function view(?Block $block = null): Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $template = $configuration->template;
        $information = $website->information;
        $entity = $block instanceof Block ? $block : $information;
        $entity->setUpdatedAt($information->entity->getUpdatedAt());
        $catalog = $this->coreLocator->em()->getRepository(Catalog::class)->findOneBy(['website' => $website->entity, 'slug' => 'agencies']);
        $catalogModel = $catalog ? CatalogModel::fromEntity($catalog, $this->coreLocator, ['onlyForUrl' => true]) : null;
        $agencies = is_object($catalogModel) ? $catalogModel->products : [];
        $agency = null;
        foreach ($agencies as $agencyModel) {
            if ($agencyModel->entity->getSlug() === $this->coreLocator->request()->query->get('agence')) {
                $agency = $agencyModel;
                break;
            }
        }

        return $this->render('front/'.$template.'/actions/information/view.html.twig', [
            'websiteTemplate' => $template,
            'website' => $website,
            'agencies' => $agencies,
            'agency' => $agency,
            'block' => $block,
            'information' => $information,
        ]);
    }
}
