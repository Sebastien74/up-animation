<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * PageManager.
 *
 * Manage admin Page form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => PageManager::class, 'key' => 'layout_page_form_manager'],
])]
class PageManager
{
    /**
     * PageManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @prePersist
     */
    public function prePersist(Page $page, Website $website): void
    {
        $this->post($page, $website);
        $this->setPageInTree($page, $website);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Page $page, Website $website): void
    {
        $this->post($page, $website);
    }

    /**
     * Global post.
     */
    private function post(Page $page, Website $website): void
    {
        $this->setIndex($page, $website);
    }

    /**
     * Set Page position.
     */
    private function setPageInTree(Page $page, Website $website): void
    {
        $position = count($this->entityManager->getRepository(Page::class)->findForTreePosition($website, $page)) + 1;
        $page->setPosition($position);
        $level = $page->getParent() instanceof Page ? $page->getParent()->getLevel() + 1 : 1;
        $page->setLevel($level);
    }

    /**
     * Set index Page.
     */
    private function setIndex(Page $page, Website $website): void
    {
        $existing = $this->entityManager->getRepository(Page::class)->findOneBy([
            'website' => $website,
            'asIndex' => true,
        ]);
        if ($existing && $existing->getId() !== $page->getId() && $page->isAsIndex()) {
            $existing->setAsIndex(false);
            $this->entityManager->persist($existing);
        }
    }
}
