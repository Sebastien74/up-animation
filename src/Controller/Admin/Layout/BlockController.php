<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Entity;
use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Module\Form\Form;
use App\Entity\Module\Form\StepForm;
use App\Form\Interface\LayoutFormFormManagerLocator;
use App\Form\Type\Layout\Block as FormType;
use App\Form\Type\Layout\Management\BackgroundColorBlockType;
use App\Form\Type\Layout\Management\BlockConfigurationType;
use App\Repository\Layout\BlockRepository;
use App\Repository\Layout\ColRepository;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * BlockController.
 *
 * Layout Block management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/layouts/zones/cols/blocks', schemes: '%protocol%')]
class BlockController extends AdminController
{
    private const array FORM_TYPES = [
        'core-action' => FormType\ActionType::class,
        'alert' => FormType\AlertType::class,
        'blockquote' => FormType\BlockquoteType::class,
        'card' => FormType\CardType::class,
        'collapse' => FormType\CollapseType::class,
        'counter' => FormType\CounterType::class,
        'icon' => FormType\IconType::class,
        'link' => FormType\LinkType::class,
        'media' => FormType\MediaType::class,
        'modal' => FormType\ModalType::class,
        'separator' => FormType\SeparatorType::class,
        'text' => FormType\TextType::class,
        'title-header' => FormType\TitleHeaderType::class,
        'title' => FormType\TitleType::class,
        'video' => FormType\VideoType::class,
        'widget' => FormType\WidgetType::class,
        'layout-catalog-features' => FormType\FeatureType::class,
    ];

    private const array FORM_TYPES_GROUPS = [
        'form' => [
            'formType' => FormType\FieldType::class,
            'template' => '',
        ],
    ];

    protected ?string $class = Layout\Block::class;

    /**
     * BlockController constructor.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    public function __construct(
        protected LayoutFormFormManagerLocator $layoutLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $layoutLocator->block();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Delete Block.
     */
    #[Route('/{interfaceName}/{interfaceEntity}/add/{col}/{blockType}/{action}',
        name: 'admin_block_add',
        defaults: ['action' => null],
        methods: 'GET|POST')]
    public function add(
        Request $request,
        string $interfaceName,
        int $interfaceEntity,
        Layout\Col $col,
        Layout\BlockType $blockType,
        ?Layout\Action $action = null): RedirectResponse
    {
        $slugBlock = $blockType->getSlug();
        $website = $this->getWebsite();
        $layoutConfiguration = $this->coreLocator->em()->getRepository(Layout\LayoutConfiguration::class)->findOneBy([
            'website' => $website->entity,
            'entity' => Layout\Page::class,
        ]);
        $isLayout = str_contains($slugBlock, 'layout') && empty(self::FORM_TYPES[$slugBlock]);
        $isForm = str_contains($slugBlock, 'form');
        $block = new Layout\Block();
        $block->setPosition(count($col->getBlocks()) + 1);
        $block->setAction($action);
        $block->setBlockType($blockType);
        $block->setBackgroundFullSize(false);

        if ('layout-title' === $slugBlock || 'title' === $slugBlock) {
            $block->setMarginBottom($layoutConfiguration->getTitleMarginBottom());
        } elseif ('video' === $slugBlock) {
            $block->setControls(true);
            $block->setSoundControls(true);
        } elseif ('separator' === $slugBlock) {
            $block->setMarginBottom(null);
        } else {
            $block->setMarginBottom($layoutConfiguration->getBlockMarginBottom());
        }

        if (str_contains($slugBlock, 'form-')) {
            $fieldConfiguration = new Layout\FieldConfiguration();
            $fieldConfiguration->setBlock($block);
            $anonymized = ['form-zip-code', 'form-phone', 'form-email'];
            $fieldConfiguration->setAnonymize(in_array($slugBlock, $anonymized));
            $block->setPaddingLeft(null);
            $block->setPaddingRight(null);
            $block->setFieldConfiguration($fieldConfiguration);
            if ('form-gdpr' === $block->getBlockType()->getSlug()) {
                $this->addGdprField($website->entity, $fieldConfiguration);
            }
        }

        if (str_contains($slugBlock, 'form-')) {
            $block->setAdminName(str_replace(' (form)', '', $blockType->getAdminName()));
        }

        $col->addBlock($block);

        $this->coreLocator->em()->persist($col);
        $this->coreLocator->em()->flush();

        if (!$isLayout && !$block->getAction() && isset(self::FORM_TYPES[$slugBlock]) || !$isLayout && $isForm || !$isLayout && $block->getAction() && $action->getEntity()) {
            return $this->redirectToRoute('admin_block_edit', [
                'website' => $request->get('website'),
                'interfaceName' => $interfaceName,
                'interfaceEntity' => $interfaceEntity,
                'col' => $block->getCol()->getId(),
                'block' => $block->getId(),
            ]);
        } elseif ($request->headers->get('referer')) {
            return $this->redirect($request->headers->get('referer'));
        } else {
            return $this->redirect($this->generateUrl('admin_layout_layout', [
                'website' => $website->id,
                'layout' => $col->getZone()->getLayout()->getId(),
            ]));
        }
    }

    /**
     * Edit Block.
     *
     * {@inheritdoc}
     */
    #[Route('/{col}/{interfaceName}/{interfaceEntity}/edit/{block}', name: 'admin_block_edit', methods: 'GET|POST')]
    public function edit(Request $request): Response
    {
        /** @var Layout\Block $block */
        $block = $this->coreLocator->em()->getRepository(Layout\Block::class)->find($request->attributes->getInt('block'));
        if (!$block) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Ce bloc n\'existe pas !!", [], 'front'));
        }

        $layout = $block->getCol()->getZone()->getLayout();
        $blockTypeSlug = $block->getBlockType()->getSlug();

        if (isset(self::FORM_TYPES[$blockTypeSlug])) {
            $this->formType = self::FORM_TYPES[$blockTypeSlug];
        }

        if (!$this->formType) {
            foreach (self::FORM_TYPES_GROUPS as $group => $configuration) {
                if (preg_match('/'.$group.'/', $blockTypeSlug)) {
                    $this->formType = $configuration['formType'];
                    $this->template = 'admin/page/layout/field.html.twig';
                    $formClass = 'form' === $request->get('interfaceName') ? Form::class : StepForm::class;
                    $this->formOptions['currentForm'] = $this->coreLocator->em()->getRepository($formClass)->find($request->attributes->getInt('interfaceEntity'));
                    $this->formOptions['layout'] = $layout;
                    break;
                }
            }
        }

        if ('core-action' === $blockTypeSlug) {
            $this->pageTitle = $this->coreLocator->translator()->trans('Bloc :', [], 'admin').' '.$this->coreLocator->translator()->trans($block->getAction()->getSlug(), [], 'entity_action');
        } else {
            $this->pageTitle = $this->coreLocator->translator()->trans('Bloc :', [], 'admin').' '.$this->coreLocator->translator()->trans($block->getBlockType()->getSlug(), [], 'entity_blocktype');
        }

        if ('icon' === $blockTypeSlug) {
            $this->arguments['stylesSrc'] = ['admin-icons-library' => 'admin'];
        }

        if ('media' === $blockTypeSlug) {
            $website = $this->getWebsite();
            $this->formManager->setMedias($block, $website->entity);
            $this->arguments['mediaRelationsTabs'] = $this->formManager->getMediaRelationsTabs($block, $website->entity);
        }

        $this->arguments['displayFrontFonts'] = true;

        $filesystem = new Filesystem();
        if ($filesystem->exists($this->coreLocator->projectDir().'\templates\admin\page\block\\'.$blockTypeSlug.'.html.twig')) {
            $this->template = 'admin/page/block/'.$blockTypeSlug.'.html.twig';
        }

        return parent::edit($request);
    }

    /**
     * Edit background color Block.
     */
    #[Route('/background/{block}', name: 'admin_block_background', options: ['expose' => true], methods: 'GET|POST')]
    public function background(Request $request): Response
    {
        $this->disableFlash = true;
        $this->template = 'admin/core/layout/background.html.twig';
        $this->formType = BackgroundColorBlockType::class;

        return parent::edit($request);
    }

    /**
     * Block[] positions update.
     */
    #[Route('/positions/pack/{data}', name: 'admin_blocks_positions', options: ['expose' => true], methods: 'POST')]
    public function positions(BlockRepository $blockRepository, ColRepository $colRepository, string $data): JsonResponse
    {
        $blocksData = explode('&', $data);
        foreach ($blocksData as $colData) {
            $matchesId = explode('=', $colData);
            $matches = explode(',', urldecode($matchesId[1]));
            $block = $blockRepository->find($matchesId[0]);
            $col = $colRepository->find($matches[0]);
            $layout = $col->getZone()->getLayout();
            $block->setPosition(intval($matches[1]));
            $block->setCol($col);
            $this->coreLocator->em()->persist($block);
            $this->coreLocator->em()->persist($layout);
        }
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Set Col size.
     */
    #[IsGranted('ROLE_EDIT')]
    #[Route('/size/{block}/{size}', name: 'admin_block_size', options: ['expose' => true], methods: 'GET')]
    public function size(Layout\Block $block, int $size): JsonResponse
    {
        $block->setSize($size);
        $this->coreLocator->em()->persist($block);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Block modal.
     */
    #[Route('/add/modal/{col}/{configuration}/{entityId}', name: 'admin_block_modal', options: ['expose' => true], methods: 'GET')]
    public function modal(Layout\Col $col, Layout\LayoutConfiguration $configuration, int $entityId): JsonResponse
    {
        return new JsonResponse(['html' => $this->renderView('admin/core/layout/new-block.html.twig', [
            'col' => $col,
            'blockTypeAction' => $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => 'core-action']),
            'entity' => $this->coreLocator->em()->getRepository($configuration->getEntity())->find($entityId),
            'interface' => $this->getInterface($configuration->getEntity()),
            'configuration' => $configuration,
        ])]);
    }

    /**
     * Edit Block configuration.
     *
     * @throws ContainerExceptionInterface|InvalidArgumentException|NonUniqueResultException|\Doctrine\ORM\Mapping\MappingException|\ReflectionException
     */
    #[Route('/modal/configuration/{block}', name: 'admin_block_configuration', methods: 'GET|POST')]
    public function configuration(Request $request): Response
    {
        $this->disableFlash = true;
        $this->entity = $this->coreLocator->em()->getRepository(Layout\Block::class)->find($request->attributes->getInt('block'));
        $this->formType = BlockConfigurationType::class;
        $this->formManager = $this->layoutLocator->blockConfiguration();
        $this->template = 'admin/core/layout/block-configuration.html.twig';
        $this->arguments['block'] = $this->entity;

        return parent::edit($request);
    }

    /**
     * Delete Block.
     *
     * {@inheritdoc}
     */
    #[Route('/{col}/delete/{block}', name: 'admin_block_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * Add GDPR field.
     */
    private function addGdprField(Website $website, Layout\FieldConfiguration $fieldConfiguration): void
    {
        $message = $this->coreLocator->translator()->trans("J'accepte que mes données soient utilisées pour me recontacter dans le cadre de cette demande.", [], 'front');

        $valueIntl = new Layout\FieldValueIntl();
        $valueIntl->setLocale($website->getConfiguration()->getLocale());
        $valueIntl->setWebsite($website);
        $valueIntl->setIntroduction($message);
        $valueIntl->setBody('true');

        $value = new Layout\FieldValue();
        $value->setAdminName('GDPR field');
        $value->addIntl($valueIntl);

        $fieldConfiguration->setRequired(true);
        $fieldConfiguration->setExpanded(true);
        $fieldConfiguration->setMultiple(true);
        $fieldConfiguration->addFieldValue($value);
    }

    /**
     * To set breadcrumb.
     *
     * @throws NonUniqueResultException|MappingException
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        $interfaceName = $request->get('interfaceName');
        $entityId = $request->get('interfaceEntity');
        $configuration = [
            'page' => \App\Entity\Layout\Page::class,
            'form' => \App\Entity\Module\Form\Form::class,
            'newscastcategory' => \App\Entity\Module\Newscast\Category::class,
            'catalog' => \App\Entity\Module\Catalog\Catalog::class,
        ];

        if ($entityId && !empty($configuration[$interfaceName])) {
            $entity = $this->coreLocator->em()->getRepository($configuration[$interfaceName])->find($entityId);
            $entityConfiguration = $this->coreLocator->em()->getRepository(Entity::class)->findOneBy([
                'website' => $request->get('website'),
                'className' => $configuration[$interfaceName],
            ]);
            $indexRoute = 'page' === $interfaceName ? 'admin_page_tree' : 'admin_'.$interfaceName.'_index';
            if (str_contains($indexRoute, 'tree')) {
                $title = $this->coreLocator->translator()->trans('Arborescence', [], 'admin_breadcrumb');
            } else {
                $breadcrumb = $this->coreLocator->translator()->trans('breadcrumb', [], 'entity_'.$interfaceName);
                $plural = $this->coreLocator->translator()->trans('plural', [], 'entity_'.$interfaceName);
                $title = 'breadcrumb' !== $breadcrumb ? $breadcrumb : ('plural' !== $plural ? $plural : $entityConfiguration->getAdminName());
            }
            $items[$title] = $indexRoute;
            if ('form' === $interfaceName && $entity && $entity->getStepform()) {
                $items[$this->coreLocator->translator()->trans('Étapes', [], 'admin_breadcrumb')] = $this->coreLocator->router()->generate('admin_'.$interfaceName.'_index', [
                    'website' => $this->getWebsite()->id,
                    'stepform' => $entity->getStepform()->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
            $items[$this->coreLocator->translator()->trans('Mise en page', [], 'admin_breadcrumb')] = $this->coreLocator->router()->generate('admin_'.$interfaceName.'_layout', [
                'website' => $this->getWebsite()->id,
                $interfaceName => $entity->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        parent::breadcrumb($request, $items);
    }
}
