<?php

declare(strict_types=1);

namespace App\Message\Axonaut;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * PushContactToAxonaut.
 *
 * Carries the scalar lead data extracted from a form submission so it can be
 * pushed to Axonaut asynchronously, without coupling the handler to Doctrine
 * entities nor blocking the front submission.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessage('async')]
final class PushContactToAxonaut
{
    public function __construct(
        public ?string $firstname = null,
        public ?string $lastname = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $company = null,
        public ?string $opportunityName = null,
        public ?string $comments = null,
    ) {
    }
}
