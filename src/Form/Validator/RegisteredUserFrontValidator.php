<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Repository\Security\UserFrontRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RegisteredUserFrontValidator.
 *
 * Validates that the submitted email belongs to a UserFront on the current website.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RegisteredUserFrontValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserFrontRepository $repository,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RegisteredUserFront) {
            throw new UnexpectedTypeException($constraint, RegisteredUserFront::class);
        }

        if (!is_string($value) || '' === $value) {
            return;
        }

        $website = $this->coreLocator->website();
        if (!$website) {
            return;
        }

        $user = $this->repository->findOneBy(['email' => $value, 'website' => $website->entity]);

        if (!$user) {
            $this->context->buildViolation($this->translator->trans($constraint->message, [], 'security_cms'))
                ->addViolation();
        }
    }
}
