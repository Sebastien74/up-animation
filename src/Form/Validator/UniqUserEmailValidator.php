<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use App\Entity\Security\UserRequest;
use App\Form\Model\Security\Admin\RegistrationFormModel;
use App\Repository\Security\UserFrontRepository;
use App\Repository\Security\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UniqUserEmailValidator.
 *
 * Check if User email already exist
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UniqUserEmailValidator extends ConstraintValidator
{
    /**
     * UniqUserEmailValidator constructor.
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserFrontRepository $userFrontRepository,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var $constraint UniqUserEmail */

        /** @var User|UserFront|UserRequest $user */
        $user = $this->context->getRoot()->getData();
        if ($user instanceof UserRequest) {
            $repository = $user->getUserFront() ? $this->userFrontRepository : $this->userRepository;
            $user = $user->getUserFront() ?: null;
        } else {
            $repository = $user instanceof RegistrationFormModel || $user instanceof User ? $this->userRepository : $this->userFrontRepository;
        }
        $existingUser = $repository->findOneBy(['email' => $value]);

        if (!$existingUser || is_object($existingUser) && method_exists($user, 'getId') && $existingUser->getId() === $user->getId()) {
            return;
        }

        $message = $this->translator->trans('Cet email existe déjà.', [], 'validators_cms');
        $this->context->buildViolation($message)->addViolation();
    }
}
