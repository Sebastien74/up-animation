<?php

declare(strict_types=1);

namespace App\Form\Type\Layout;

use App\Entity\Core\Configuration;
use App\Entity\Core\Module;
use App\Entity\Core\Website;
use App\Entity\Layout\BlockType;
use App\Entity\Layout\Page;
use App\Form\Widget as WidgetType;
use App\Repository\Module\Menu\MenuRepository;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PageType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PageType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private bool $isInternalUser;
    private bool $haveBackgroundsRole;
    private ?Website $website;

    /**
     * PageType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly MenuRepository $menuRepository,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
        $this->haveBackgroundsRole = $this->isInternalUser || in_array('ROLE_BACKGROUND_PAGE', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Page $page */
        $page = $builder->getData();
        $isNew = !$page->getId();
        $this->website = $options['website'];
        $secureModule = $this->entityManager->getRepository(Module::class)->findOneBy(['role' => 'ROLE_SECURE_PAGE']);
        $secureActive = $secureModule ? $this->entityManager->getRepository(Configuration::class)->moduleExist($this->website, $secureModule) : null;
        $pagesNavModule = $this->entityManager->getRepository(Module::class)->findOneBy(['slug' => 'pages-navigation']);
        $pagesNavActive = $pagesNavModule ? $this->entityManager->getRepository(Configuration::class)->moduleExist($this->website, $pagesNavModule) : null;
        $zonesNavModule = $this->entityManager->getRepository(BlockType::class)->findOneBy(['slug' => 'zones-navigation']);
        $zonesNavActive = $zonesNavModule ? $this->entityManager->getRepository(Configuration::class)->blockTypeExist($this->website, $zonesNavModule) : null;
        $intlNavActive = $pagesNavActive || $zonesNavActive;
        $mainMenu = $this->menuRepository->findMain($this->website);

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew ? 'col-md-4' : ($this->isInternalUser ? 'col-sm-9' : 'col-12'),
            'slug-internal' => $this->isInternalUser,
            'class' => 'refer-code',
        ]);

        if (!$page->isInfill()) {
            if ($isNew) {
                $builder->add('parent', EntityType::class, [
                    'required' => false,
                    'display' => 'search',
                    'label' => $this->translator->trans('Page parente', [], 'admin'),
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'class' => Page::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('p')
                            ->leftJoin('p.urls', 'u')
                            ->andWhere('p.website = :website')
                            ->andWhere('u.archived = :archived')
                            ->setParameter('website', $this->website)
                            ->setParameter('archived', false)
                            ->orderBy('p.adminName', 'ASC');
                    },
                    'choice_label' => function ($page) {
                        return strip_tags($page->getAdminName());
                    },
                    'row_attr' => ['class' => 'col-md-4'],
                ]);
            }

            $templateClass = $isNew ? 'col-md-4' : (!$this->haveBackgroundsRole ? 'col-12' : 'col-md-6');
            $builder->add('template', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Template', [], 'admin'),
                'display' => 'search',
                'choices' => $this->getTemplates($page),
                'attr' => ['group' => $templateClass, 'data-config' => true],
            ]);

            if (!$isNew) {
                $urls = new WidgetType\UrlsCollectionType($this->coreLocator);
                $urls->add($builder, ['display_seo' => true]);
            }

            if ($isNew) {
                if ($mainMenu) {
                    $builder->add('inMenu', Type\CheckboxType::class, [
                        'required' => false,
                        'mapped' => false,
                        'display' => 'button',
                        'color' => 'outline-info-darken',
                        'label' => $this->translator->trans('Afficher dans le menu', [], 'admin'),
                        'attr' => ['group' => $secureActive ? 'col-md-4 text-center' : 'col-md-6 text-center', 'class' => 'w-100'],
                    ]);
                }

                $builder->add('infill', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Page intercalaire', [], 'admin'),
                    'attr' => ['group' => $secureActive && $mainMenu ? 'col-md-4 text-center' : 'col-md-6 text-center', 'class' => 'w-100'],
                ]);
            }

            if ($isNew && $secureActive) {
                $builder->add('secure', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Page sécurisée', [], 'admin'),
                    'attr' => ['group' => !$mainMenu ? 'col-md-6 text-center' : 'col-md-4 text-center', 'class' => 'w-100'],
                ]);
            }

            if (!$isNew) {

                $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
                $intls->add($builder, [
                    'website' => $options['website'],
                    'fields' => ['title' => 'col-12'],
                    'label_fields' => ['title' => $this->translator->trans('Titre du plan de site', [], 'admin')],
                    'title_force' => false,
                    'disableTitle' => true,
                    'data_config' => !$page->isInfill(),
                ]);

                $dates = new WidgetType\PublicationDatesType($this->coreLocator);
                $dates->add($builder, [
                    'startGroup' => $this->haveBackgroundsRole || !$this->isInternalUser ? 'col-12' : 'col-md-6',
                    'endGroup' => $this->haveBackgroundsRole || !$this->isInternalUser ? 'col-12' : 'col-md-6',
                ]);

                if ($this->haveBackgroundsRole) {
                    $builder->add('backgroundColor', WidgetType\BackgroundColorSelectType::class, [
                        'label' => $this->translator->trans('Couleur de fond', [], 'admin'),
                        'attr' => [
                            'data-config' => true,
                            'class' => 'select-icons',
                            'group' => 'col-md-6',
                        ],
                    ]);
                }

                $builder->add('asIndex', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans("Page d'accueil", [], 'admin'),
                    'attr' => ['data-config' => true, 'group' => 'col-md-6', 'class' => 'w-100'],
                ]);

                $builder->add('infill', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Page intercalaire', [], 'admin'),
                    'attr' => ['data-config' => true, 'group' => 'col-md-6', 'class' => 'w-100'],
                ]);

                $builder->add('pictogram', WidgetType\PictogramType::class, [
                    'attr' => ['group' => 'col-md-6'],
                ]);

                if ($secureActive) {
                    $builder->add('secure', Type\CheckboxType::class, [
                        'required' => false,
                        'display' => 'button',
                        'color' => 'outline-info-darken',
                        'label' => $this->translator->trans('Page sécurisée', [], 'admin'),
                        'attr' => ['data-config' => true, 'group' => 'col-md-6', 'class' => 'w-100'],
                    ]);
                }

                if ($this->haveBackgroundsRole) {
                    $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
                    $fields = $intlNavActive ? ['title'] : ['title'];
                    $mediaRelations->add($builder, [
                        'data_config' => true,
                        'entry_options' => [
                            'onlyMedia' => true,
                            'forceIntl' => true,
                            'intlTitleForce' => false,
                            'label_fields' => ['intl' => [
                                'title' => $intlNavActive
                                    ? $this->translator->trans('Titre de la sous navigation', [], 'admin')
                                    : $this->translator->trans('Titre', [], 'admin')
                            ]],
                            'fields' => ['intl' => $fields],
                            'excludes_fields' => ['intl' => ['targetStyle', 'newTab', 'externalLink']],
                        ],
                    ]);
                }
            }
        } else {
            $builder->add('infill', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Page intercalaire', [], 'admin'),
                'attr' => ['group' => 'col-md-3 text-center', 'class' => 'w-100'],
            ]);

            if ($secureActive) {
                $builder->add('secure', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Page sécurisée', [], 'admin'),
                    'attr' => ['group' => 'col-md-3 text-center', 'class' => 'w-100'],
                ]);
            }
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get front templates.
     */
    private function getTemplates(Page $page): array
    {
        $componentsTemplate = 'components.html.twig';
        $disabledTemplates = ['error'];

        $finder = Finder::create();
        $templateDir = !$this->website ? 'default' : $this->website->getConfiguration()->getTemplate();
        $templates = [];
        $frontDir = $this->coreLocator->projectDir().'/templates/front/'.$templateDir.'/template/';
        $frontDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $frontDir);
        $finder->files()->in($frontDir)->depth([0]);

        foreach ($finder as $file) {
            if ($page->getTemplate() !== $componentsTemplate && $file->getFilename() !== $componentsTemplate
                || $page->getTemplate() === $componentsTemplate && $file->getFilename() === $componentsTemplate) {
                $code = str_replace('.html.twig', '', $file->getFilename());
                $templateName = $this->getTemplateName($code);
                if (!in_array($code, $disabledTemplates) && 'build' !== $code) {
                    $templates[$templateName] = $file->getFilename();
                }
            }
        }
        $templates[$this->getTemplateName('build')] = 'build.html.twig';

        return $templates;
    }

    /**
     * To get template name.
     */
    private function getTemplateName(string $code): string
    {
        $names = [
            'cms' => $this->translator->trans('Standard', [], 'admin'),
            'home' => $this->translator->trans('Accueil', [], 'admin'),
            'legacy' => $this->translator->trans('Mentions légales', [], 'admin'),
            'build' => $this->translator->trans('Maintenance', [], 'admin'),
        ];

        return !empty($names[$code]) ? $names[$code] : ucfirst($code);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
            'website' => null,
            'translation_domain' => 'admin',
            'allow_extra_fields' => true,
        ]);
    }
}
