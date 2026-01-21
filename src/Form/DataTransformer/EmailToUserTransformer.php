<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Security\User;
use App\Repository\Security\UserRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * EmailToUserTransformer.
 *
 * Transform Email to User
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EmailToUserTransformer implements DataTransformerInterface
{
    /**
     * EmailToUserTransformer constructor.
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly \Closure $finderCallback)
    {
    }

    public function transform(mixed $value): ?string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof User) {
            throw new \LogicException('The UserEmailSelectType can only be used with User objects');
        }

        return $value->getEmail();
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (!$value) {
            return '';
        }

        $callback = $this->finderCallback;
        $user = $callback($this->userRepository, $value);

        if (!$user) {
            throw new TransformationFailedException(sprintf('No user found with email "%s"', $value));
        }

        return $user;
    }
}
