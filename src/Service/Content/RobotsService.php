<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Layout\Page;
use App\Model\Core\WebsiteModel;
use App\Service\Core\InterfaceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Routing\RouterInterface;

/**
 * RobotsServices.
 *
 * Manage robots.txt
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RobotsService
{
    private const bool DISALLOW_ARCHIVED = false;

    /**
     * RobotsService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InterfaceHelper $interfaceHelper,
        private readonly SitemapService $sitemapService,
        private readonly RouterInterface $router,
    ) {
    }

    /**
     * Execute robots service.
     *
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public function execute(WebsiteModel $website): array
    {
        $configuration = $website->configuration;

        return [
            'disallow' => !$configuration->seoStatus,
            'noIndexes' => $this->getNoIndexes($website),
        ];
    }

    /**
     * Get noIndex urls.
     *
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    private function getNoIndexes(WebsiteModel $website): array
    {
        $urls = $defaultUris = [];
        $urls[] = ['uri' => '/*axeptio'];
        $urls[] = ['uri' => '/*axeptio'];
        $urls[] = ['uri' => '/denied.php'];
        $urls[] = ['uri' => $this->router->generate('front_favorites_index', [], RouterInterface::ABSOLUTE_URL)];
        foreach ($urls as $url) {
            $defaultUris[] = $url['uri'];
        }

        if (self::DISALLOW_ARCHIVED) {
            $metasData = $this->entityManager->getMetadataFactory()->getAllMetadata();
            $this->sitemapService->setVars($website->entity, 'fr');
            foreach ($metasData as $metadata) {
                $classname = $metadata->getName();
                $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
                if ($baseEntity && method_exists($baseEntity, 'getUrls') && method_exists($baseEntity, 'getWebsite')) {
                    $interface = $this->interfaceHelper->generate($classname);
                    $entities = $this->sitemapService->getEntities($classname, $baseEntity);
                    $entities = $this->sitemapService->getEntities($classname, $baseEntity, $entities);
                    $indexPagesCodes = $this->sitemapService->getIndexPages($entities, $interface);
                    foreach ($entities as $entity) {
                        foreach ($entity->entity->getUrls() as $url) {
                            if ($entity->entity instanceof Page && (!$url->isAsIndex() || $url->isHideInSitemap() || $entity->entity->isInfill())) {
                                $page = $this->sitemapService->setPage($entity, $url, $interface);
                                if ($page) {
                                    $urls[] = $page;
                                }
                            } elseif (!$url->isAsIndex()) {
                                $page = $this->sitemapService->setAsCard($entity, $interface, $url, $indexPagesCodes);
                                if ($page) {
                                    $urls[] = $page;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($urls as $key => $url) {
            $stop = true;
            foreach ($defaultUris as $uri) {
                if (str_contains($uri, $url['uri'])) {
                    $stop = false;
                    break;
                }
            }
            if ($stop) {
                $urls[$key]['uri'] = $url['uri'].'$';
            }
        }

        return $urls;
    }
}
