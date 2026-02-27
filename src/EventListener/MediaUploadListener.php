<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Media\Media;
use App\Service\Content\ImageThumbnailInterface;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Media\Compressor;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MediaUploadListener.
 *
 * Handle image resizing and compression on upload.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class MediaUploadListener implements EventSubscriberInterface
{
    public function __construct(
        private CoreLocatorInterface $coreLocator,
        private ImageThumbnailInterface $imageThumbnail,
        private Compressor $compressor,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_UPLOAD => 'onVichUploaderPreUpload',
        ];
    }

    public function onVichUploaderPreUpload(Event $event): void
    {
        $object = $event->getObject();

        if (!$object instanceof Media) {
            return;
        }

        $file = $object->getImageFile();
        if (!$file instanceof UploadedFile) {
            return;
        }

        $this->processImage($file, $object);
    }

    private function processImage(UploadedFile $file, Media $media): void
    {
        $path = $file->getRealPath();
        $mimeType = $file->getMimeType();

        if (!str_starts_with($mimeType, 'image/')) {
            return;
        }

        if (str_contains($mimeType, 'svg') || str_contains($mimeType, 'gif')) {
            return;
        }

        $sizes = getimagesize($path);
        if (!$sizes) {
            return;
        }

        [$width, $height] = $sizes;
        $maxWidth = $this->imageThumbnail->getMaxFileWidth();
        $maxHeight = $this->imageThumbnail->getMaxFileHeight();

        // Récupérer l'orientation si JPEG
        $orientation = 1;
        if (($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;
        }

        // Si l'orientation indique une rotation de 90° ou 270°, on inverse les dimensions cibles pour le calcul du ratio
        $swapped = in_array($orientation, [5, 6, 7, 8]);
        $currentWidth = $swapped ? $height : $width;
        $currentHeight = $swapped ? $width : $height;

        $resize = false;
        $targetWidth = $currentWidth;
        $targetHeight = $currentHeight;

        if ($currentWidth > $maxWidth) {
            $resize = true;
            $targetHeight = (int) (($currentHeight * $maxWidth) / $currentWidth);
            $targetWidth = $maxWidth;
        }
        if ($targetHeight > $maxHeight) {
            $resize = true;
            $targetWidth = (int) (($targetWidth * $maxHeight) / $targetHeight);
            $targetHeight = $maxHeight;
        }

        $mustCompress = $file->getSize() > 500 * 1024; // 500ko

        if ($resize || $mustCompress || $orientation !== 1) {
            $this->resizeAndCompress($path, $mimeType, $targetWidth, $targetHeight, $media, $orientation);
            // Compression adaptative si le fichier est toujours > 500ko après resize
            if (file_exists($path) && filesize($path) > 500 * 1024) {
                $this->compressor->optimize($path, $mimeType, $media->getQuality() ?? 85);
            }
        }
    }

    private function resizeAndCompress(string $path, string $mime, int $width, int $height, Media $media, int $orientation = 1): void
    {
        $sourceImage = null;
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $sourceImage = imagecreatefromjpeg($path);
        } elseif ($mime === 'image/png') {
            $sourceImage = imagecreatefrompng($path);
        } elseif ($mime === 'image/webp') {
            $sourceImage = imagecreatefromwebp($path);
        }

        if (!$sourceImage) {
            return;
        }

        // 1. Gérer l'orientation AVANT le redimensionnement si possible, ou pendant.
        // On effectue la rotation sur l'image source pour avoir les bonnes dimensions de départ.
        if ($orientation !== 1) {
            if ($orientation === 3) {
                $sourceImage = imagerotate($sourceImage, 180, 0);
            } elseif ($orientation === 6) {
                $sourceImage = imagerotate($sourceImage, -90, 0);
            } elseif ($orientation === 8) {
                $sourceImage = imagerotate($sourceImage, 90, 0);
            }
            // Note: les cas 2, 4, 5, 7 (miroirs) sont rares mais pourraient être gérés ici si besoin.
        }

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);
        $newImage = imagecreatetruecolor($width, $height);

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        $quality = 85;
        if ($media->getQuality() > 0 && $media->getQuality() < 100) {
            $quality = $media->getQuality();
        }

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            imageinterlace($newImage, true);
            imagejpeg($newImage, $path, $quality);
        } elseif ($mime === 'image/png') {
            imagepng($newImage, $path, 9);
        } elseif ($mime === 'image/webp') {
            imagewebp($newImage, $path, $quality);
        }

        imagedestroy($sourceImage);
        imagedestroy($newImage);

        $session = $this->coreLocator->request()?->hasSession() ? $this->coreLocator->request()->getSession() : null;
        if ($session) {
            $session->getFlashBag()->add('info', $this->coreLocator->translator()->trans('Votre média %filename% a été optimisé (redimensionné ou compressé).', [
                '%filename%' => $media->getName() ?? $media->getFilename() ?? 'téléchargé',
            ], 'admin'));
        }
    }

    private function autoOrient(string $path, string $mime): void
    {
        // Cette méthode est maintenant intégrée à resizeAndCompress pour plus d'efficacité
    }
}
