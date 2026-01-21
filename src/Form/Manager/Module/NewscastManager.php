<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Newscast\Newscast;
use App\Entity\Security\UserFront;
use App\Form\Interface\CoreFormManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * NewscastManager.
 *
 * Manage Newscast form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => NewscastManager::class, 'key' => 'module_newscast_form_manager'],
])]
class NewscastManager
{
    /**
     * NewscastManager constructor.
     */
    public function __construct(private readonly CoreFormManagerInterface $coreLocator)
    {
    }

    /**
     * @prePersist
     *
     * @throws MappingException
     */
    public function prePersist(Newscast $newscast, Website $website, array $interface = [], ?Form $form = null, ?UserFront $userFront = null): void
    {
        $this->coreLocator->base()->prePersist($newscast, $website, $userFront);
    }
}
