<?php

declare(strict_types=1);

namespace App\Form\Manager\Gdpr;

use App\Entity\Core\Website;
use App\Entity\Gdpr\Cookie;
use App\Form\Manager\Core\BaseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * CookieManager.
 *
 * Manage Cookie admin form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CookieManager::class, 'key' => 'gdpr_cookie_form_manager'],
])]
class CookieManager
{
    /**
     * CookieManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @prePersist
     */
    public function prePersist(Cookie $cookie, Website $website): void
    {
        $cookie->setAdminName($cookie->getCode());
        $cookie->setSlug($cookie->getCode());
        $this->entityManager->persist($cookie);
    }
}
