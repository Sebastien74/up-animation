<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Module\Slider\Slider;
use App\Model\MediasModel;
use App\Service\Content\ImageUpscaler;
use Exception;
use Psr\Cache\InvalidArgumentException;
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
     * @throws Exception|InvalidArgumentException
     */
    public function view(
        Request $request,
        ImageUpscaler $imageUpscaler,
        ?Block $block = null,
        mixed $filter = null,
    ): Response {

        $slider = $filter ? $this->coreLocator->em()->getRepository(Slider::class)->findOneByWithRelations(
            is_numeric($filter) ? 'id' : 'slug',
            $filter
        ) : false;

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
        // Tailles de crop des vignettes par écran (embeddable CropSizes) -> option screensSizes du rendu.
        $cropSizes = $slider->getCropSizes();
        $screensSizes = $cropSizes->isDefined() ? $cropSizes->toScreensSizes() : [];
        // Agrandit les sources trop petites pour honorer les tailles de crop demandées.
        if ($cropSizes->isDefined()) {
            $imageUpscaler->ensureCropSizes($slider->getMediaRelations(), $cropSizes);
        }

        return $this->cache($request, 'front/'.$template.'/actions/slider/view.html.twig', $slider, [
            'websiteTemplate' => $template,
            'mainPages' => $website->configuration->pages,
            'block' => $block,
            'isHomePage' => !$uri || '/' === $uri,
            'website' => $website,
            'thumbConfiguration' => $thumbConfiguration,
            'slider' => $slider,
            'arrowsSide' => $arrowsAlignment && str_contains($arrowsAlignment, 'start') ? 'start' : ($arrowsAlignment && str_contains($arrowsAlignment, 'end') ? 'end' : 'center'),
            'arrowsAsBtn' => $arrowsColor && str_contains($arrowsColor, 'btn'),
            'arrowsColor' => $arrowsColor ? str_replace(['btn-', 'text-'], '', $arrowsColor) : 'primary',
            'medias' => MediasModel::fromEntity($slider, $this->coreLocator)->mediasAndVideos,
            'screensSizes' => $screensSizes,
        ]);
    }
}
