<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Media\Media;
use App\Service\Content\ImageThumbnailInterface;
use App\Service\Interface\CoreLocatorInterface;
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
class MediaUploadListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly ImageThumbnailInterface $imageThumbnail
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

        $this->autoOrient($path, $mimeType);

        $sizes = getimagesize($path);
        if (!$sizes) {
            return;
        }

        [$width, $height] = $sizes;
        $maxWidth = $this->imageThumbnail->getMaxFileWidth();
        $maxHeight = $this->imageThumbnail->getMaxFileHeight();

        $resize = false;
        if ($width > $maxWidth) {
            $resize = true;
            $height = (int) (($height * $maxWidth) / $width);
            $width = $maxWidth;
        }
        if ($height > $maxHeight) {
            $resize = true;
            $width = (int) (($width * $maxHeight) / $height);
            $height = $maxHeight;
        }

        $mustCompress = $file->getSize() > 500 * 1024; // 500ko

        if ($resize || $mustCompress) {
            $this->resizeAndCompress($path, $mimeType, $width, $height, $media);
        }
    }

    private function resizeAndCompress(string $path, string $mime, int $width, int $height, Media $media): void
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
            imagejpeg($newImage, $path, $quality);
        } elseif ($mime === 'image/png') {
            imagepng($newImage, $path, (int) round(9 * $quality / 100));
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
        if (($mime !== 'image/jpeg' && $mime !== 'image/jpg') || !function_exists('exif_read_data')) {
            return;
        }

        $exif = @exif_read_data($path);
        $orientation = isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;

        if ($orientation === 1) {
            return;
        }

        $img = @imagecreatefromjpeg($path);
        if (!$img) {
            return;
        }

        if ($orientation === 3) {
            $img = imagerotate($img, 180, 0);
        } elseif ($orientation === 6) {
            $img = imagerotate($img, -90, 0);
        } elseif ($orientation === 8) {
            $img = imagerotate($img, 90, 0);
        }

        imagejpeg($img, $path, 95);
        imagedestroy($img);
    }
}
