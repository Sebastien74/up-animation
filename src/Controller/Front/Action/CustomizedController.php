<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Model\Module\ProductModel;
use App\Service\Content\ActionService;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * CustomizedController.
 *
 * Customized renders or actions
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/front/customized/action', schemes: '%protocol%')]
class CustomizedController extends FrontController
{
    /**
     * Zone Contact-us.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|ReflectionException|QueryException
     */
    public function zoneContactUs(ActionService $actionService): Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $mainPages = $website->configuration->pages;
        $websiteTemplate = $configuration->template;

        $email = !empty($website->emails) ? $website->emails[0] : false;
        $phone = !empty($website->phones) ? $website->phones[0] : false;
        $address = !empty($website->addresses) ? $website->addresses[0] : false;
        $city = false;
        $contactPageUrl = !empty($mainPages['contact']) && $mainPages['contact']->code ? $mainPages['contact']->code : false;
        $contactPageParams = $contactPageUrl ? ['url' => $contactPageUrl] : [];

        $route = $this->coreLocator->request()->attributes->get('_route');
        $url = $this->coreLocator->request()->attributes->get('url');

        $entity = null;
        if ($url && $route && str_contains($route, 'catalogproduct')) {
            $entity = $actionService->findEntityByUrlAndLocale($url);
            $entity = $entity ? ProductModel::fromEntity($entity, $this->coreLocator, ['disabledIntl' => true, 'disabledMedias' => true, 'disabledUrl' => true, 'disabledCategories' => true, 'disabledCategory' => true]) : null;
            if ($entity) {
                $contactPageParams['agence'] = $entity->slug;
                $address = $entity->address;
                $city = $entity->city;
            }
        }

        return $this->render('front/'.$websiteTemplate.'/actions/customized/contact-us.html.twig', [
            'mainPages' => $website->configuration->pages,
            'configuration' => $configuration,
            'websiteTemplate' => $websiteTemplate,
            'website' => $website,
            'entity' => $entity,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'contactUrl' => $contactPageUrl ? $this->coreLocator->router()->generate('front_index', $contactPageParams, UrlGeneratorInterface::ABSOLUTE_URL) : null,
        ]);
    }
}
