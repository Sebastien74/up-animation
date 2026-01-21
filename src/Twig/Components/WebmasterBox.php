<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Seo\Url;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * WebmasterBox.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsLiveComponent]
class WebmasterBox
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?string $interfaceName = null;

    #[LiveProp]
    public ?int $entityId = null;

    #[LiveProp]
    public ?Url $url = null;

    public mixed $entity = null;

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function getToolbox(): array
    {
        $userAllowed = $this->coreLocator->user() && $this->coreLocator->authorizationChecker()->isGranted('ROLE_ADMIN');
        $btnArgs = ['website' => $this->coreLocator->website()->id, $this->interfaceName => $this->entityId];
        if ($this->entity && is_object($this->entity) && property_exists($this->entity, 'catalog') && !method_exists($this->entity, 'getCatalog')) {
            $btnArgs['catalog'] = $this->entity->catalog->id;
        }

        return [
            'allowIp' => $this->coreLocator->checkIP(),
            'userAllowed' => $userAllowed,
            'website' => $this->coreLocator->website(),
            'interfaceName' => $this->interfaceName,
            'entityId' => $this->entityId,
            'url' => $this->url,
            'btnEditionArgs' => $btnArgs,
        ];
    }
}
