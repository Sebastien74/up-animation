<?php

declare(strict_types=1);

namespace App\Controller\Front\Module;

use App\Controller\Front\FrontController;
use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Service\Content\LocaleService;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * LocaleController.
 *
 * Front Locale switcher render & management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LocaleController extends FrontController
{
    private const bool MULTI_WEBSITES = false;
    private const bool DISPLAY_FLAGS = true;
    private const bool DISPLAY_CURRENT_FLAG = true;
    private const bool DISPLAY_INLINE = false;
    private const bool DISPLAY_FULL_NAME = false;

    /**
     * Locale switcher View.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    #[Route('/html/render/locales/switcher/view',
        name: 'front_locales_switcher',
        options: ['isMainRequest' => false, 'expose' => true],
        methods: 'GET',
        schemes: '%protocol%')]
    public function switcher(Request $request, LocaleService $localeService): Response
    {
        $website = $this->getWebsite();
        $class = $request->get('class');
        $classname = $request->get('classname');
        $entityId = $request->get('entityId');

        $websites = [];
        $routes = [];
        if (self::MULTI_WEBSITES) {
            $websites = $this->coreLocator->em()->getRepository(Website::class)->findForSwitcher();
            if (count($websites) > 1) {
                foreach ($websites as $otherWebsite) {
                    $otherWebsite = WebsiteModel::fromEntity($otherWebsite, $this->coreLocator);
                    $routes = array_merge($localeService->execute($otherWebsite), $routes);
                }
            }
        } else {
            $entity = $classname && $entityId ? $this->coreLocator->em()->createQueryBuilder()->select('e')
                ->from(urldecode($classname), 'e')
                ->andWhere('e.id = :id')
                ->setParameter('id', $entityId)
                ->getQuery()
                ->getOneOrNullResult() : null;
            $routes = $localeService->execute($website, $entity);
            if (count($routes) !== count($website->configuration->allLocales)) {
                $domains = $localeService->getLocalesWebsites($website);
                foreach ($website->configuration->allLocales as $locale) {
                    if (empty($routes[$locale]) && !empty($domains[$locale])) {
                        $routes[$locale] = $domains[$locale];
                    }
                }
            }
        }

        return $this->render('front/'.$website->configuration->template.'/include/locale-switcher.html.twig', [
            'class' => urldecode($class),
            'asMulti' => self::MULTI_WEBSITES,
            'flags' => self::DISPLAY_FLAGS,
            'currentFlag' => self::DISPLAY_CURRENT_FLAG,
            'inline' => self::DISPLAY_INLINE,
            'fullName' => self::DISPLAY_FULL_NAME,
            'websites' => $websites,
            'currentWebsite' => $website,
            'configuration' => $website->configuration,
            'routes' => $routes,
        ]);
    }
}
