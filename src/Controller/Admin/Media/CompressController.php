<?php

declare(strict_types=1);

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminController;
use App\Entity\Media\Media;
use App\Service\Content\ImageThumbnailInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CompressController.
 *
 * To compress images
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MEDIA_COMPRESS')]
#[Route('/admin-%security_token%/{website}/medias/compress', schemes: '%protocol%')]
class CompressController extends AdminController
{
    private const string API_KEY = 'ztJQLm328f60m4M6wpg1s3Xyv488VC9V';

    /**
     * To compress image.
     *
     * doc : https://tinypng.com/developers
     * doc : https://tinypng.com/developers/reference/php
     * account dev : https://tinify.com/dashboard/api#token/kgyRPwsGCKcvZgBg7JSPjjvBfy6PPHTB/Zfpn7vqwzXUdTqrKo
     * account seb : https://tinify.com/dashboard/api#token/k4wrZSP5yRcqwbNVXTy2X1QNnLGfwXpN/7EXcm43P3fVhmuo6nVWVQ
     */
    #[Route('/execute/{media}', name: 'admin_media_compress', options: ['expose' => true], methods: 'GET')]
    public function execute(ImageThumbnailInterface $imageThumbnail, string $projectDir, Media $media): JsonResponse
    {
        if (in_array($media->getExtension(), $imageThumbnail->getAllowedExtensions())) {
            $filesystem = new Filesystem();
            $websiteDirname = $this->formatDirname($projectDir.'/public/uploads/'.$media->getWebsite()->getUploadDirname().'/');
            $imageDirname = $this->formatDirname($websiteDirname.'/'.$media->getFilename());
            $originalDirname = $this->formatDirname($websiteDirname.'/original/');

            if (!$filesystem->exists($originalDirname)) {
                $filesystem->mkdir($originalDirname);
            }

            if ($filesystem->exists($imageDirname) && !$filesystem->exists($originalDirname.$media->getFilename())) {
                $filesystem->copy($imageDirname, $originalDirname.$media->getFilename());
            }

            if ($filesystem->exists($imageDirname)) {
                try {
                    \Tinify\setKey(self::API_KEY);
                    $source = \Tinify\fromFile($imageDirname);
                } catch (\Tinify\AccountException $e) {
                    /* Verify your API key and account limit. */
                    return $this->exceptionResponse($media, $e);
                } catch (\Tinify\ClientException $e) {
                    /* Check your source image and request options. */
                    return $this->exceptionResponse($media, $e);
                } catch (\Tinify\ServerException $e) {
                    /* Temporary issue with the Tinify API. */
                    return $this->exceptionResponse($media, $e);
                } catch (\Tinify\ConnectionException $e) {
                    /* A network connection error occurred. */
                    return $this->exceptionResponse($media, $e);
                } catch (\Exception $e) {
                    /* Something else went wrong, unrelated to the Tinify API. */
                    return $this->exceptionResponse($media, $e);
                }

                $source->toFile($imageDirname);

                $media->setCompress(true);
                $this->coreLocator->em()->persist($media);
                $this->coreLocator->em()->flush();
            }

            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => '<strong>'.$media->getFilename().'</strong> '.$this->coreLocator->translator()->trans('Extension ('.$media->getExtension().') non valide.'),
        ]);
    }

    /**
     * To restore original image.
     */
    #[Route('/restore/{media}', name: 'admin_media_compress_restore', options: ['expose' => true], methods: 'GET')]
    public function restore(string $projectDir, Media $media): JsonResponse
    {
        $existing = false;
        $filesystem = new Filesystem();
        $websiteDirname = $this->formatDirname($projectDir.'/public/uploads/'.$media->getWebsite()->getUploadDirname().'/');
        $imageDirname = $this->formatDirname($websiteDirname.'/'.$media->getFilename());
        $originalDirname = $this->formatDirname($websiteDirname.'/original/'.$media->getFilename());

        if ($filesystem->exists($originalDirname)) {
            $filesystem->copy($originalDirname, $imageDirname);
            $filesystem->remove($originalDirname);
            $existing = true;
        }

        $media->setCompress(false);
        $this->coreLocator->em()->persist($media);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => $existing]);
    }

    /**
     * To format dirname.
     */
    private function formatDirname(string $dirname): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
    }

    /**
     * To get exception JsonResponse.
     */
    private function exceptionResponse(Media $media, mixed $exception): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => '<strong>'.$media->getFilename().'</strong> '.$exception->getMessage(),
        ]);
    }
}
