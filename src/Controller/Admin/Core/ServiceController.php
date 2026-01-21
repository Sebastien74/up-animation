<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Seo\Url;
use App\Service\Content\SeoService;
use App\Service\Core\Urlizer;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ServiceController.
 *
 * Admin service management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/services', schemes: '%protocol%')]
class ServiceController extends AdminController
{
    /**
     * Code generator.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|\ReflectionException|QueryException
     */
    #[Route('/code-generator/{url}',
        name: 'admin_code_generator',
        options: ['expose' => true],
        defaults: ['url' => null],
        methods: 'GET')]
    public function generateCode(Request $request, SeoService $seoService, ?Url $url = null): JsonResponse
    {
        $string = $request->get('string');
        $classname = $request->get('classname');
        $entityId = $request->get('entityId');

        if (!$url instanceof Url && 'undefined' === $string) {
            return new JsonResponse(['code' => '']);
        }

        if ($url instanceof Url && $classname) {
            if (!$url->getWebsite()) {
                $url->setWebsite($this->getWebsite()->entity);
            }
            $request->setLocale($url->getLocale());
            $request->getSession()->set('_locale', $url->getLocale());
            $entity = $this->coreLocator->em()->getRepository(urldecode($classname))->find($entityId);
            $seo = $seoService->execute($url, $entity, $url->getLocale());
            $string = !$string && is_array($seo) && !empty($seo['titleH1']) ? $seo['titleH1'] : (!$string && is_array($seo) && !empty($seo['title']) ? $seo['title'] : $string);
            $request->setLocale($url->getWebsite()->getConfiguration()->getLocale());
            $request->getSession()->set('_locale', $url->getWebsite()->getConfiguration()->getLocale());
        }

        return new JsonResponse(['code' => Urlizer::urlize(strip_tags(urldecode($string)))]);
    }
}
