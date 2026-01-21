<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Media\Media;
use App\Entity\Media\Thumb;
use App\Entity\Media\ThumbConfiguration;
use App\Form\Manager\Core\GlobalManager;
use App\Form\Type\Media\ThumbType;
use App\Repository\Media\ThumbRepository;
use App\Twig\Content\ThumbnailRuntime;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CropController.
 *
 * Media crop management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MEDIA')]
#[Route('/admin-%security_token%/{website}/medias/cropper', schemes: '%protocol%')]
class CropController extends AdminController
{
    /**
     * Crop Media Index.
     */
    #[Route('/index/{media}', name: 'admin_media_crop', methods: 'GET')]
    public function cropIndex(Website $website, Media $media, ThumbnailRuntime $thumbnailRuntime)
    {
        return $this->adminRender('admin/page/media/crop.html.twig', [
            'media' => $media,
            'thumbs' => $thumbnailRuntime->mediaThumbs($website, $media),
        ]);
    }

    /**
     * Crop Media Index.
     */
    #[Route('/cropper/{media}/{thumbConfiguration}', name: 'admin_media_cropper', methods: 'GET|POST')]
    public function cropper(
        Request $request,
        GlobalManager $globalManager,
        Media $media,
        ThumbRepository $thumbRepository,
        ThumbConfiguration $thumbConfiguration)
    {
        $mediaThumb = $thumbRepository->findOneBy([
            'media' => $media,
            'configuration' => $thumbConfiguration,
        ]);

        if (!$mediaThumb) {
            $mediaThumb = new Thumb();
            $mediaThumb->setMedia($media)
                ->setConfiguration($thumbConfiguration)
                ->setWidth($thumbConfiguration->getWidth())
                ->setHeight($thumbConfiguration->getHeight());
            $media->addThumb($mediaThumb);
        }

        $form = $this->createForm(ThumbType::class, $mediaThumb);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mediaThumb = $form->getData();
            $this->coreLocator->em()->persist($mediaThumb);
            $this->coreLocator->em()->flush();
            return $this->redirect($request->headers->get('referer'));
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $globalManager->invalid($form);
            return $this->redirect($request->headers->get('referer'));
        }

        return $this->adminRender('admin/page/media/cropper.html.twig', [
            'form' => $form->createView(),
            'media' => $media,
            'isInfinite' => $thumbConfiguration->getWidth() < 1 && $thumbConfiguration->getHeight() < 1,
            'thumb' => $mediaThumb,
            'thumbConfiguration' => $mediaThumb->getConfiguration(),
            'configuration' => $thumbConfiguration,
        ]);
    }
}
