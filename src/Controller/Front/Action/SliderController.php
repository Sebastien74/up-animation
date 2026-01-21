<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Module\Slider\Slider;
use App\Model\MediasModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SliderController.
 *
 * Front Slider renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SliderController extends FrontController
{
    /**
     * View.
     *
     * @throws \Exception
     */
    public function view(
        Request $request,
        ?Block $block = null,
        mixed $filter = null,
    ): Response {

        if (!$filter) {
            return new Response();
        }

        $cache = $this->coreLocator->cacheService()->cachePool($block, 'slider_view', 'GET');
        if ($cache) {
            return $cache;
        }

        $slider = $this->coreLocator->em()->getRepository(Slider::class)->findOneByWithRelations(
            is_numeric($filter) ? 'id' : 'slug',
            $filter
        );
        if (!$slider) {
            return new Response();
        }

        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $template = $configuration->template;

        $thumbConfiguration = $this->thumbConfiguration($website, Slider::class, 'view', $slider)
            ?? $this->thumbConfiguration($website, Slider::class, 'view');

        $uri = $this->coreLocator->request()->getPathInfo();
        $arrowsAlignment = $slider->getArrowAlignment();
        $arrowsColor = $slider->getArrowColor();

        $response = $this->cache($request, 'front/'.$template.'/actions/slider/view.html.twig', $slider, [
            'websiteTemplate' => $template,
            'block' => $block,
            'isHomePage' => !$uri || '/' === $uri,
            'website' => $website,
            'thumbConfiguration' => $thumbConfiguration,
            'slider' => $slider,
            'arrowsSide' => $arrowsAlignment && str_contains($arrowsAlignment, 'start') ? 'start' : ($arrowsAlignment && str_contains($arrowsAlignment, 'end') ? 'end' : 'center'),
            'arrowsAsBtn' => $arrowsColor && str_contains($arrowsColor, 'btn'),
            'arrowsColor' => $arrowsColor ? str_replace(['btn-', 'text-'], '', $arrowsColor) : 'primary',
            'medias' => MediasModel::fromEntity($slider, $this->coreLocator)->mediasAndVideos,
        ]);

        return $this->coreLocator->cacheService()->cachePool($block, 'slider_view', 'GENERATE', $response);
    }
}
