<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * WebmasterEdit.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsTwigComponent]
class WebmasterEdit
{
    public ?string $title = null;
    public string $path = '#';
    public ?string $role = null;
    
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function isVisible(): bool
    {
        return $this->coreLocator->user() && $this->coreLocator->authorizationChecker()->isGranted('ROLE_ADMIN');
    }
}
