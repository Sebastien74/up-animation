<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Entity\Seo\NotFoundUrl;
use App\Entity\Seo\Redirection;
use App\Form\Type\Seo\RedirectionType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * NotFoundUrlController.
 *
 * SEO NotFoundUrl management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/seo/not-found-urls', schemes: '%protocol%')]
class NotFoundUrlController extends AdminController
{
    protected ?string $class = NotFoundUrl::class;

    /**
     * Index NotFoundUrl.
     *
     * {@inheritdoc}
     */
    #[Route('/{type}/{category}', name: 'admin_notfoundurl_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $all = [];
        $website = $this->getWebsite();
        $repository = $this->coreLocator->em()->getRepository(NotFoundUrl::class);
        $configuration = [
            'front' => ['url', 'resource'],
            'admin' => ['url', 'resource'],
        ];

        foreach ($configuration as $type => $categories) {
            foreach ($categories as $category) {
                $all[$type][$category] = $repository->findByCategoryTypeQuery($website->entity, $category, $type)->getResult();
            }
        }

        /** Unset URL if domain not configured for current website */
        $domainsDB = $this->coreLocator->em()->getRepository(Domain::class)->findBy(['configuration' => $website->configuration->entity]);
        $domains = [];
        foreach ($domainsDB as $domainDB) {
            $domains[] = $domainDB->getName();
        }
        foreach ($all as $type => $categories) {
            foreach ($categories as $category => $urls) {
                foreach ($urls as $key => $url) {
                    $unset = true;
                    foreach ($domains as $domain) {
                        if (preg_match('/'.$domain.'/', $url->getUrl())) {
                            $unset = false;
                            break;
                        }
                    }
                    if ($unset) {
                        unset($all[$type][$category][$key]);
                    }
                }
            }
        }

        $this->arguments['all'] = $all;
        $this->arguments['type'] = $request->get('type');
        $this->arguments['category'] = $request->get('category');
        $this->template = 'admin/page/seo/notfound-url.html.twig';

        $this->forceEntities = true;
        if (isset($all[$this->arguments['type']][$this->arguments['category']])) {
            $this->entities = $all[$this->arguments['type']][$this->arguments['category']];
        } else {
            throw $this->createNotFoundException();
        }

        return parent::index($request, $paginator);
    }

    /**
     * Set new Redirection.
     */
    #[Route('/redirection/new/{notfoundUrl}', name: 'admin_notfoundurl_redirection', methods: 'GET|POST')]
    public function newRedirection(Request $request, NotFoundUrl $notfoundUrl)
    {
        $notfoundUrl->setHaveRedirection(true);
        $this->coreLocator->em()->persist($notfoundUrl);
        $this->class = Redirection::class;
        $this->formType = RedirectionType::class;
        $this->formOptions['not_found_url'] = $notfoundUrl;
        $this->arguments['notfoundUrl'] = $notfoundUrl;
        $this->template = 'admin/page/seo/notfound-url-redirection.html.twig';

        return parent::new($request);
    }

    /**
     * Delete NotFoundUrl.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{notfoundurl}', name: 'admin_notfoundurl_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * Delete NotFoundUrl.
     */
    #[IsGranted('ROLE_DELETE')]
    #[Route('/delete/all/{type}/{category}', name: 'admin_notfoundurl_delete_all', methods: 'DELETE')]
    public function deleteAll(Request $request, Website $website, string $type, string $category): JsonResponse
    {
        $notFounds = $this->coreLocator->em()->getRepository(NotFoundUrl::class)->findByCategoryTypeQuery(
            $website,
            $category,
            $type
        )->getResult();

        foreach ($notFounds as $notFound) {
            $this->coreLocator->em()->remove($notFound);
        }

        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true, 'reload' => true]);
    }
}
