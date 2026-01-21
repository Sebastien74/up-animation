<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Form\Interface\LayoutFormManagerInterface;
use App\Form\Manager\Core\BaseDuplicateManager;
use App\Service\Core\Uploader;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * ZoneDuplicateManager.
 *
 * Manage admin Zone duplication form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ZoneDuplicateManager::class, 'key' => 'layout_zone_duplicate_form_manager'],
])]
class ZoneDuplicateManager extends BaseDuplicateManager
{
    /**
     * ZoneDuplicateManager constructor.
     */
    public function __construct(
        private readonly LayoutFormManagerInterface $layoutManager,
        protected string $projectDir,
        protected CoreLocatorInterface $coreLocator,
        protected EntityManagerInterface $entityManager,
        protected Uploader $uploader,
        protected RequestStack $requestStack,
    ) {
        parent::__construct($projectDir, $coreLocator, $entityManager, $uploader, $requestStack);
    }

    /**
     * Execute duplication.
     */
    public function execute(Layout\Zone $zone, Website $website, Form $form): void
    {
        /** @var Layout\Page $destinationPage */
        $destinationPage = null;
        foreach ($form->all() as $name => $value) {
            if ('zone' !== $name) {
                $destinationPage = $value->getData();
                break;
            }
        }

        $layout = $destinationPage->getLayout();
        $session = new Session();

        if (is_object($destinationPage) && method_exists($destinationPage, 'getWebsite')) {
            $session->set('DUPLICATE_TO_WEBSITE_FROM_ZONE', $destinationPage->getWebsite());
        }

        /** @var Layout\Zone $zoneToDuplicate */
        $zoneToDuplicate = $form->get('zone')->getData();
        $this->coreLocator->em()->refresh($zoneToDuplicate);

        $this->addZone($zoneToDuplicate, $zone, $layout, $website);
        $zone->setPosition(count($layout->getZones()) + 1);
        $this->coreLocator->em()->persist($zone);
        $this->layoutManager->layout()->setGridZone($layout);
        $session->remove('DUPLICATE_TO_WEBSITE_FROM_ZONE');
    }

    /**
     * Duplicate Zones.
     */
    public function addZones(Layout\Layout $layout, Collection $zonesToDuplicate, Website $website): void
    {
        foreach ($zonesToDuplicate as $zoneToDuplicate) {
            $zone = new Layout\Zone();
            $this->addZone($zoneToDuplicate, $zone, $layout, $website);
        }
    }

    /**
     * Duplicate Zone.
     */
    public function addZone(Layout\Zone $zoneToDuplicate, Layout\Zone $zone, Layout\Layout $layout, Website $website): void
    {
        $this->setByProperties($zone, $zoneToDuplicate);
        $zone->setLayout($layout);
        $this->entityManager->persist($zone);
        $this->entityManager->flush();
        $this->entityManager->refresh($zone);
        $this->layoutManager->colDuplicate()->addCols($zone, $zoneToDuplicate->getCols());
    }
}
