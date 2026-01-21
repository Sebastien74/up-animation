<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Seo\Redirection;
use App\Form\Interface\SeoFormManagerLocator;
use App\Form\Type\Seo\ImportRedirectionType;
use App\Form\Type\Seo\RedirectionType;
use App\Form\Type\Seo\WebsiteRedirectionType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * RedirectionController.
 *
 * SEO Redirection management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_SEO')]
#[Route('/admin-%security_token%/{website}/seo/redirections', schemes: '%protocol%')]
class RedirectionController extends AdminController
{
    protected ?string $class = Redirection::class;
    protected ?string $formType = RedirectionType::class;

    /**
     * RedirectionController constructor.
     */
    public function __construct(
        protected SeoFormManagerLocator $seoLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Redirection.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_redirection_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $this->formType = null;
        $this->class = Redirection::class;
        $this->template = 'admin/page/seo/redirection-index.html.twig';

        return parent::index($request, $paginator);
    }

    /**
     * Edit Redirection.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|ReflectionException|ContainerExceptionInterface
     */
    #[Route('/edit', name: 'admin_redirection_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->entity = $this->getWebsite();
        $this->class = Website::class;
        $this->formType = WebsiteRedirectionType::class;
        $this->formManager = $this->seoLocator->redirection();
        $this->template = 'admin/page/seo/redirection.html.twig';

        return parent::edit($request);
    }

    /**
     * Edit Form Redirection.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|ReflectionException|ContainerExceptionInterface
     */
    #[Route('/form/{redirection}', name: 'admin_redirection_form', methods: 'GET|POST')]
    public function form(Request $request, Website $website, Redirection $redirection)
    {
        $this->entity = $redirection;
        $this->formType = RedirectionType::class;
        $this->formManager = $this->seoLocator->redirection();
        $this->formOptions = ['labels' => false, 'groups' => 'col-12'];
        $this->template = 'admin/page/seo/redirection-form.html.twig';

        return parent::edit($request);
    }

    /**
     * New Redirection.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_redirection_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * New Redirection.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|Exception
     */
    #[Route('/import', name: 'admin_redirection_import', methods: 'GET|POST')]
    public function import(Request $request): RedirectResponse|JsonResponse|Response
    {
        $form = $this->createForm(ImportRedirectionType::class);
        $form->handleRequest($request);
        $arguments['form'] = $form->createView();
        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->seoLocator->importRedirection()->execute($form, $this->getWebsite()->entity);
            return new JsonResponse(['success' => $response, 'flashBag' => !$response, 'html' => $this->renderView('admin/page/seo/import.html.twig', $arguments)]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            return new JsonResponse(['html' => $this->renderView('admin/page/seo/import.html.twig', $arguments)]);
        }

        return $this->adminRender('admin/page/seo/import.html.twig', $arguments);
    }

    /**
     * Export Redirection[].
     *
     * {@inheritdoc}
     */
    #[Route('/export', name: 'admin_redirection_export', methods: 'GET|POST')]
    public function export(Request $request)
    {
        $this->class = Redirection::class;

        return parent::export($request);
    }

    /**
     * Delete Redirection.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    #[Route('/delete/{redirection}', name: 'admin_redirection_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $redirectionManager = $this->seoLocator->redirection();
        $redirectionManager->clearCache();
        $this->class = Redirection::class;

        return parent::delete($request);
    }
}
