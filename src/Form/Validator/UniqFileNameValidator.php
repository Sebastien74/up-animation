<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Core\Website;
use App\Entity\Media\Media;
use App\Repository\Media\MediaRepository;
use App\Service\Core\Urlizer;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UniqFileNameValidator.
 *
 * Check if filename already exist
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UniqFileNameValidator extends ConstraintValidator
{
    /**
     * UniqFileValidator constructor.
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly MediaRepository $mediaRepository)
    {
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var Media $media */
        $entity = $this->context->getRoot()->getData();

        if ($entity instanceof Media) {
            $website = $entity->getWebsite();
            $filename = Urlizer::urlize($entity->getName());
            $existingMedia = $website instanceof Website
                ? $this->mediaRepository->findOneBy(['name' => $filename, 'website' => $website])
                : $this->mediaRepository->findOneBy(['name' => $filename]);

            if ($existingMedia && $existingMedia !== $entity) {
                $message = $this->translator->trans('Un autre fichier porte déjà ce nom !', [], 'validators_cms').' ('.$filename.')';
                $this->context->buildViolation(rtrim($message, '<br/>'))->addViolation();
                $session = new Session();
                $session->set('same_file_error', rtrim($message, '<br/>'));
            }

            if ($entity->getFilename() && !$entity->getName()) {
                $message = $this->translator->trans('This value should not be blank.', [], 'validators');
                $this->context->buildViolation(rtrim($message, '<br/>'))->addViolation();
            }
        }
    }
}
