<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Module\Newscast\Newscast;
use App\Model\Module\NewscastModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\QueryException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Date.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsLiveComponent]
class LastNews
{
    use DefaultActionTrait;

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get last published news.
     *
     * @throws QueryException|MappingException
     */
    public function getLastNews(): array
    {
        $locale = $this->coreLocator->locale();
        $website = $this->coreLocator->website();
        $entity = $this->coreLocator->em()->getRepository(Newscast::class)->findMaxResultPublishedOrderByNewest($locale, $website->entity, 1);

        return [
            'website' => $website,
            'entity' => $entity ? NewscastModel::fromEntity($entity, $this->coreLocator) : null,
        ];
    }
}