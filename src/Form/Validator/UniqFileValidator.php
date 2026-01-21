<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Media\Media;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * UniqFileValidator.
 *
 * Check if file already exist
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UniqFileValidator extends ConstraintValidator
{
    /**
     * UniqFileValidator constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $message = '';
        $values = is_array($value) ? $value : [$value];
        $violation = false;
        $website = $this->coreLocator->website();
        $mediaPost = method_exists($this->context->getObject()->getParent(), 'getNormData') ? $this->context->getObject()->getParent()->getNormData() : null;

        if ($values) {
            foreach ($values as $value) {
                if ($value) {
                    /* @var $value UploadedFile */
                    $originalFilename = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);
                    $filename = Urlizer::urlize($originalFilename);
                    $filesystem = new Filesystem();
                    $extension = 'heic' === $value->guessExtension() ? 'jpg' : $value->guessExtension();
                    $existingFile = $filesystem->exists($this->coreLocator->uploadDir().'/'.$website->uploadDirname.'/'.$filename.'.'.$extension);
                    if ($existingFile) {
                        $existingMedia = $this->coreLocator->em()->getRepository(Media::class)->findOneBy(['filename' => $filename.'.'.$extension, 'website' => $website->entity]);
                        if ($existingMedia && $existingMedia->getId() !== $mediaPost->getId()) {
                            $violation = true;
                            $message .= $this->coreLocator->translator()->trans('Un autre fichier porte déjà ce nom !', [], 'validators_cms').' ('.$value->getClientOriginalName().')<br/>';
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
}
