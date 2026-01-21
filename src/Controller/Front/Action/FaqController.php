<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Module\Faq\Faq;
use App\Entity\Module\Faq\Question;
use App\Model\Module\FaqModel;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * FaqController.
 *
 * Front Faq renders
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class FaqController extends FrontController
{
    /**
     * View.
     *
     * @throws NonUniqueResultException|MappingException
     */
    #[Route('/front/faq/view/{filter}', name: 'front_faq_view', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function view(?Block $block = null, mixed $filter = null): Response
    {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'faq_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $faqModel = FaqModel::fromEntity($this->coreLocator, false, $block, $filter);
        $faq = $faqModel->entity;

        if (!$faq instanceof Faq) {
            return new Response();
        }

        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        $entity = $block instanceof Block ? $block : $faq;
        $entity->setUpdatedAt($faq->getUpdatedAt());

        $response = $this->render('front/'.$websiteTemplate.'/actions/faq/view.html.twig', [
            'view' => 'view',
            'configuration' => $configuration,
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
            'thumbConfiguration' => $this->thumbConfiguration($website, Question::class, 'view'),
            'faq' => $faqModel,
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'faq_view', 'GENERATE', $response);
    }

    /**
     * Teaser.
     *
     * @throws NonUniqueResultException|MappingException
     */
    #[Route('/front/faq/teaser/{filter}', name: 'front_faq_teaser', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function teaser(?Block $block = null, mixed $filter = null): Response
    {
        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'faq_teaser', 'GET');
        if ($cache) {
            return $cache;
        }

        $faqModel = FaqModel::fromEntity($this->coreLocator, true, $block, $filter);
        $faq = $faqModel->entity;

        if (!$faq instanceof Faq) {
            return new Response();
        }

        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        $entity = $block instanceof Block ? $block : $faq;
        $entity->setUpdatedAt($faq->getUpdatedAt());

        $response = $this->render('front/'.$websiteTemplate.'/actions/faq/view.html.twig', [
            'view' => 'teaser',
            'configuration' => $configuration,
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
            'thumbConfiguration' => $this->thumbConfiguration($website, Question::class, 'view'),
            'faq' => $faqModel,
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'faq_teaser', 'GENERATE', $response);
    }
}
