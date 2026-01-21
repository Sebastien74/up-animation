<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Form;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Form\Form;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Form\FormType;
use App\Model\IntlModel;
use App\Service\Admin\FormDuplicateInterface;
use App\Service\Core\Urlizer;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FormController.
 *
 * Form Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_FORM')]
#[Route('/admin-%security_token%/{website}/forms', schemes: '%protocol%')]
class FormController extends AdminController
{
    protected ?string $class = Form::class;
    protected ?string $formType = FormType::class;

    /**
     * FormController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->form();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Form.
     *
     * {@inheritdoc}
     */
    #[Route('/index/{stepform}', name: 'admin_form_index', defaults: ['stepform' => null], methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        if (!empty($request->get('stepform'))) {
            $this->pageTitle = $this->coreLocator->translator()->trans('Étapes', [], 'admin');
        }

        return parent::index($request, $paginator);
    }

    /**
     * New Form.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_form_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Layout Form.
     *
     * {@inheritdoc}
     */
    #[Route('/layout/{form}/{stepform}', name: 'admin_form_layout', defaults: ['stepform' => null], methods: 'GET|POST')]
    public function layout(Request $request)
    {
        $form = $this->coreLocator->em()->getRepository(Form::class)->find($request->attributes->getInt('form'));
        if ($form) {
            $excluded = ['form-gdpr', 'form-submit'];
            foreach ($form->getLayout()->getZones() as $zone) {
                foreach ($zone->getCols() as $col) {
                    foreach ($col->getBlocks() as $block) {
                        $intl = IntlModel::fromEntity($block, $this->coreLocator);
                        $title = $intl->title ?: $block->getAdminName();
                        $blockTypeSlug = $block->getBlockType()->getSlug();
                        $configuration = $block->getFieldConfiguration();
                        $configurationSlug = $configuration ? $configuration->getSlug() : Urlizer::urlize($block->getAdminName());
                        if (!in_array($blockTypeSlug, $excluded) && $configuration && $title && $configurationSlug) {
                            $this->formOptions['fieldsHelp'][$title] = $configurationSlug;
                        }
                    }
                }
            }
            $this->formOptions['fieldsHelp']["Nom de l'entreprise"] = 'companyName';
        }

        return parent::layout($request);
    }

    /**
     * Duplicate Form.
     *
     * {@inheritdoc}
     */
    #[Route('/duplicate/{form}', name: 'admin_form_duplicate', methods: 'GET')]
    public function duplicateForm(Request $request, FormDuplicateInterface $service): \Symfony\Component\HttpFoundation\RedirectResponse|JsonResponse
    {
        $website = $this->getWebsite();
        $form = $this->coreLocator->em()->getRepository(Form::class)->find($request->get('form'));

        if (!$form || $form->getStepform()) {
            return $this->redirectToRoute('admin_form_index', ['website' => $website->id]);
        }

        $newForm = $service->execute($form, $this->getUser());

        return new JsonResponse(['redirection' => $this->generateUrl('admin_form_layout', [
            'website' => $website->entity->getId(),
            'form' => $newForm->getId(),
        ])]);
    }

    /**
     * Show Form.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{form}', name: 'admin_form_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Form.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{form}', name: 'admin_form_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Form.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{form}', name: 'admin_form_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('stepform')) {
            $items[$this->coreLocator->translator()->trans('Formulaires', [], 'admin_breadcrumb')] = 'admin_stepform_index';
        }
        if ($request->get('form') && !$request->isMethod('post')) {
            $form = $this->coreLocator->em()->getRepository(Form::class)->find($request->get('form'));
            if ($form && $form->getStepform()) {
                $items[$this->coreLocator->translator()->trans('Formulaires', [], 'admin_breadcrumb')] = 'admin_stepform_index';
                $items[$this->coreLocator->translator()->trans('Étapes', [], 'admin_breadcrumb')] = $this->coreLocator->router()->generate('admin_form_index', [
                    'website' => $this->getWebsite()->id,
                    'stepform' => $form->getStepform()->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            } else {
                $items[$this->coreLocator->translator()->trans('Formulaires', [], 'admin_breadcrumb')] = 'admin_form_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
