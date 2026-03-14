<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * WebmasterEdit.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsLiveComponent('WebmasterEdit')]
class WebmasterEdit
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?string $title = null;

    #[LiveProp]
    public string $path = '#';

    #[LiveProp]
    public ?string $role = null;

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function isVisible(): bool
    {
        return $this->coreLocator->user() && $this->coreLocator->authorizationChecker()->isGranted('ROLE_ADMIN');
    }
}
