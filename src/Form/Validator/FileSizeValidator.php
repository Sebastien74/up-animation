<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Service\Content\ImageThumbnailInterface;
use App\Twig\Content\FileRuntime;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FileSizeValidator.
 *
 * Check if is valid mobile phone
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FileSizeValidator extends ConstraintValidator
{
    private const bool CHECK_WIDTH = false;
    private const bool CHECK_HEIGHT = false;
    private const bool CHECK_WEIGHT = true;

    /**
     * FileSizeValidator constructor.
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ImageThumbnailInterface $imageThumbnail,
        private readonly FileRuntime $fileRuntime,
    ) {
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $message = '';
        $files = is_array($value) ? $value : [$value];
        $violation = false;
        $maxWidth = $this->imageThumbnail->getMaxFileWidth();
        $maxHeight = $this->imageThumbnail->getMaxFileHeight();
        $maxSizeThumb = $this->imageThumbnail->getMaxFileSize();
        $serveurMaxFileSize = $this->fileRuntime->convertToBytesSize(ini_get('upload_max_filesize'));
        $maxSize = min($serveurMaxFileSize, $maxSizeThumb);
        $maxSizeBytes = $this->fileRuntime->formatBytes($maxSize);

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                if (in_array($file->getClientOriginalExtension(), $this->imageThumbnail->getAllowedExtensions())) {
                    $sizes = getimagesize($file->getRealPath());
                    $isImage = @is_array($sizes);
                    $width = !empty($sizes[0]) ? $sizes[0] : null;
                    $height = !empty($sizes[1]) ? $sizes[1] : null;
                    if (self::CHECK_WIDTH && $isImage && $width > $maxWidth) {
                        $violation = true;
                        $message .= $this->translator->trans('Le fichier '.$file->getClientOriginalName().' est trop large ('.$width.'px). Sa largeur ne doit pas dépasser '.$maxWidth.'px.', [], 'validators_cms').'<br/>';
                    }
                    if (self::CHECK_HEIGHT && $isImage && $height > $maxHeight) {
                        $violation = true;
                        $message .= $this->translator->trans('Le fichier '.$file->getClientOriginalName().' est trop haut ('.$height.'px). Sa hauteur ne doit pas dépasser '.$maxHeight.'px.', [], 'validators_cms').'<br/>';
                    }
                    if (self::CHECK_WEIGHT && $isImage && $file->getSize() > $maxSize) {
                        $violation = true;
                        $fileSizeBytes = $this->fileRuntime->formatBytes($file->getSize());
                        $message .= $this->translator->trans('Le fichier '.$file->getClientOriginalName().' est trop volumineux ('.$fileSizeBytes.'). Sa taille ne doit pas dépasser '.$maxSizeBytes, [], 'validators_cms').'.<br/>';
                    }
                }
            }
        }

        if ($violation) {
            $message = rtrim($message, '<br/>');
            $session = new Session();
            $session->set('same_file_error', $message);
            $this->context->buildViolation($message)->addViolation();
        }
    }
}
