<?php

declare(strict_types=1);

namespace App\Service\Development\Cms;

use App\Entity\Core\Website;
use App\Form\Manager\Layout\LayoutManager;
use App\Service\Doctrine\SqlServiceInterface;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * ImportService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImportService
{
    protected ?UserInterface $user;
    protected array $websites = [];
    protected array $entities = [];
    protected int $position = 0;
    protected ?string $classname = null;
    protected ?string $folderName = null;
    protected ?string $folderSlug = null;

    /**
     * ImportService constructor.
     */
    public function __construct(
        protected readonly CoreLocatorInterface $coreLocator,
        protected readonly SqlServiceInterface $sqlService,
        protected readonly LayoutManager $layoutManager,
    ) {
        if ($this->coreLocator->request() && 4 == $this->coreLocator->request()->get('version')) {
            $this->setWebsites();
        }
        $this->user = $this->getUser();
    }

    /**
     * To get position.
     */
    protected function setPosition(Website $website, string $classname): int
    {
        $referEntity = new $classname();
        $repository = $this->coreLocator->em()->getRepository($classname);
        if (method_exists($referEntity, 'getWebsite')) {
            $this->position = count($repository->findBy(['website' => $website])) + 1;
        } else {
            $this->position = count($repository->findAll()) + 1;
        }

        return $this->position;
    }

    /**
     * To get User.
     */
    protected function getUser(): ?UserInterface
    {
        $token = $this->coreLocator->tokenStorage()->getToken();

        return is_object($token) && method_exists($token, 'getUser') && method_exists($token->getUser(), 'getId') ? $token->getUser() : null;
    }

    /**
     * To set websites.
     */
    protected function setWebsites(string $table = 'fxc_sites', string $languagesTable = 'fxc_languages'): void
    {
        if (4 == $this->coreLocator->request()->get('version')) {
            $this->sqlService->setConnection('direct');
            $websites = $this->sqlService->findAll($table);
            foreach ($websites as $website) {
                if (!empty($website['language_id'])) {
                    $language = $this->sqlService->find($languagesTable, 'id', $website['language_id']);
                    if (!empty($language['language_code'])) {
                        $this->websites[$website['id']]['locale'] = $language['language_code'];
                    }
                }
            }
        }
    }
}
