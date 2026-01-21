<?php

declare(strict_types=1);

namespace App\Service\Development;

use App\Command\DoctrineCommand;
use App\Entity\Core\Module;
use App\Entity\Core\Website;
use App\Entity\Layout\Action;
use App\Entity\Layout\BlockType;
use App\Entity\Layout\LayoutConfiguration;
use App\Entity\Layout\Page;
use App\Entity\Security\Role;
use App\Entity\Security\User;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Yaml\Yaml;

/**
 * CopyBundleService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CopyBundleService implements CopyBundleInterface
{
    /**
     * CopyBundleService construct.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly DoctrineCommand $doctrineCommand,
    ) {
    }

    /**
     * To execute service.
     *
     * @throws InvalidArgumentException
     */
    public function execute(): void
    {
        $excluded = ['faker', 'phone-number-bundle'];
        $bundlesDirname = $this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'sfcms';
        $finder = new Finder();
        $finder->directories()->in($bundlesDirname)->depth(0);
        $count = 0;
        foreach ($finder->directories() as $directory) {
            if (!in_array($directory->getFilename(), $excluded)) {
                ++$count;
            }
        }

        if ($count > 0) {
            $websites = $this->coreLocator->em()->getRepository(Website::class)->findAll();
            foreach ($finder->directories() as $directory) {
                $composerFinder = new Finder();
                $composerFinder->files()->in($directory->getRealPath())->name('composer.json')->contains('app:copy:bundle')->depth(0);
                if (1 === $composerFinder->count()) {
                    $composerFile = null;
                    foreach ($composerFinder->files() as $file) {
                        $composerFile = $file;
                        break;
                    }
                    if ($composerFile) {
                        $bundleConfig = $this->getBundleConfig($composerFile);
                        $appDirectories = ['assets', 'bin', 'src', 'templates'];
                        foreach ($appDirectories as $appDirectory) {
                            $srcFinder = new Finder();
                            $srcFinder->files()->in($directory->getRealPath().DIRECTORY_SEPARATOR.$appDirectory.DIRECTORY_SEPARATOR);
                            foreach ($srcFinder->files() as $file) {
                                $this->copyFile($file, $appDirectory, $bundleConfig);
                            }
                        }
                        $this->setConfiguration($bundleConfig, $websites);
                        $this->doctrineCommand->update();
                    }
                }
            }
        }
    }

    /**
     * To copy file.
     */
    private function copyFile(\SplFileInfo $file, string $splitter, object $bundleConfig): void
    {
        $filesystem = new Filesystem();
        $matches = explode(DIRECTORY_SEPARATOR.$splitter.DIRECTORY_SEPARATOR, $file->getRealPath());
        $copyDirname = $this->coreLocator->projectDir().DIRECTORY_SEPARATOR.$splitter.DIRECTORY_SEPARATOR.end($matches);
        if (!$filesystem->exists($copyDirname)) {
            $filesystem->copy($file->getRealPath(), $copyDirname);
            if (str_contains($copyDirname, 'admin\include\sidebar\include')) {
                $this->adminLinkSidebar($copyDirname);
            }
        } elseif ('layout.js' === $file->getFilename()) {
            $this->frontLayoutJS($file, $copyDirname);
        } elseif (str_contains($file->getRealPath(), 'front') && str_contains($file->getRealPath(), 'components') && str_contains($file->getFilename(), '.scss')) {
            $this->frontCSS($file);
        }
        if (str_contains($file->getRealPath(), 'bin') && str_contains($file->getRealPath(), 'fixtures')) {
            $this->addFixtures($file);
        }
    }

    /**
     * To get bundle name.
     */
    private function getBundleConfig(\SplFileInfo $composerFile): object
    {
        $file = new File($composerFile->getRealPath());
        $jsonDecoder = new JsonDecode();
        $data = $jsonDecoder->decode($file->getContent(), 'json');
        $name = is_object($data) && property_exists($data, 'name') ? $data->name : '';
        $config = is_object($data) && property_exists($data, 'config') ? $data->config : null;
        $matches = explode('/', $name);
        $matches = explode('-', end($matches));
        $slug = is_object($config) && property_exists($config, 'slug') ? $config->slug : $matches[0];

        return (object) [
            'name' => is_object($data) && property_exists($data, 'name') ? $data->name : null,
            'slug' => $slug,
            'title' => is_object($config) && property_exists($config, 'title') ? $config->title : ucfirst($name),
            'roles' => is_object($config) && property_exists($config, 'roles') ? $config->roles : [],
            'module' => is_object($config) && property_exists($config, 'module') ? $config->module : null,
            'actions' => is_object($config) && property_exists($config, 'actions') ? $config->actions : [],
            'layouts' => is_object($config) && property_exists($config, 'layouts') ? $config->layouts : [],
            'blockTypes' => is_object($config) && property_exists($config, 'blockTypes') ? $config->blockTypes : [],
        ];
    }

    /**
     * To add module link in admin sidebar.
     */
    private function adminLinkSidebar(string $copyDirname): void
    {
        $sidebarDirname = $this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'sidebar'.DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'modules.html.twig';
        $appFile = new File($sidebarDirname);
        $appContent = $appFile->getContent();
        $copyFile = new File($copyDirname);
        $copyContent = "{% include 'admin/include/sidebar/include/modules/".$copyFile->getFilename()."' %}";
        $matches = explode('</ul>', $appContent);
        $content = $matches[0]."\t".$copyContent.PHP_EOL."\t\t".'</ul>'.$matches[1];
        if (!str_contains($appContent, $copyContent)) {
            file_put_contents($sidebarDirname, $content);
        }
    }

    /**
     * To add front JS in layout.
     */
    private function frontLayoutJS(\SplFileInfo $file, string $copyDirname): void
    {
        $appFile = new File($copyDirname);
        $appContent = $appFile->getContent();
        $copyFile = new File($file->getRealPath());
        $copyContent = $copyFile->getContent();
        $content = $appContent.PHP_EOL.PHP_EOL.$copyContent;
        if ($this->insertContent($appContent, $copyContent)) {
            file_put_contents($copyDirname, $content);
        }
    }

    /**
     * To add front CSS.
     */
    private function frontCSS(\SplFileInfo $file): void
    {
        $filesystem = new Filesystem();
        $matches = explode('scss\front\\', $file->getRealPath());
        $matches = explode('\\', end($matches));
        if (!empty($matches[0])) {
            $vendorDirname = $this->coreLocator->projectDir().'\assets\scss\front\\'.$matches[0].'\vendor.scss';
            if ($filesystem->exists($vendorDirname)) {
                $vendorFile = new File($vendorDirname);
                $vendorContent = $vendorFile->getContent();
                $pushContent = false;
                if ($this->insertContent($vendorContent, 'Externals Modules')) {
                    $vendorContent .= PHP_EOL.PHP_EOL.'/*-----------------------------------'.PHP_EOL."\t\t".'Externals Modules'.PHP_EOL.'-----------------------------------*/'.PHP_EOL;
                }
                if ($this->insertContent($vendorContent, '@import "components/'.$file->getFilename().'";')) {
                    $vendorContent .= PHP_EOL.'@import "components/'.$file->getFilename().'";';
                    $pushContent = true;
                }
                if ($pushContent) {
                    file_put_contents($vendorDirname, $vendorContent);
                }
            }
        }
    }

    /**
     * To add front CSS.
     */
    private function addFixtures(\SplFileInfo $file): void
    {
        $configurationDirname = $this->coreLocator->projectDir().'\bin\data\fixtures\entity-configuration.yaml';
        $config = Yaml::parseFile($configurationDirname);
        $inImport = false;
        foreach ($config['imports'] as $import) {
            if (str_contains($import['resource'], $file->getFilename())) {
                $inImport = true;
                break;
            }
        }
        if (!$inImport) {
            $config['imports'][] = ['resource' => 'entity/'.$file->getFilename()];
            file_put_contents($configurationDirname, Yaml::dump($config));
        }
    }

    /**
     * To set configuration.
     *
     * @throws InvalidArgumentException
     */
    private function setConfiguration(object $bundleConfig, array $websites): void
    {
        $this->setUsersRole($bundleConfig);
        $module = $this->addModule($bundleConfig, $websites);
        $blockTypes = $this->addBlockTypes($bundleConfig);
        $this->addModuleToLayout($websites, $module);
        $this->addActions($bundleConfig, $module);
        $this->addLayout($bundleConfig, $module, $websites, $blockTypes);
    }

    /**
     * To remove package.
     *
     * @throws InvalidArgumentException|Exception
     */
    private function setUsersRole(object $bundleConfig): void
    {
        $users = $this->coreLocator->em()->getRepository(User::class)->findAll();
        $roles = (array) $bundleConfig->roles;

        $securityDirname = $this->coreLocator->projectDir().'\bin\data\fixtures\security.yaml';
        $config = Yaml::parseFile($securityDirname);
        foreach ($roles as $roleName => $locales) {
            if (empty($config[$roleName])) {
                $config[$roleName] = (array) $locales;
                file_put_contents($securityDirname, Yaml::dump($config));
            }
        }

        foreach ($bundleConfig->roles as $roleName => $locales) {
            $role = $this->coreLocator->em()->getRepository(Role::class)->findOneBy(['name' => $roleName]);
            if (!$role) {
                $position = count($this->coreLocator->em()->getRepository(Role::class)->findAll()) + 1;
                $role = new Role();
                $role->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $role->setAdminName($roleName);
                $role->setName($roleName);
                $role->setSlug(Urlizer::urlize($roleName));
                $role->setPosition($position);
                $this->coreLocator->em()->persist($role);
                $this->coreLocator->em()->flush();
            }

            foreach ($users as $user) {
                $roles = $user->getRoles();
                $asOnly = false;
                foreach ($roles as $userRole) {
                    if (str_contains($userRole, 'ONLY')) {
                        $asOnly = true;
                        break;
                    }
                }
                if (!$asOnly && !in_array($role->getName(), $roles)) {
                    $group = $user->getGroup();
                    $group->addRole($role);
                    $this->coreLocator->em()->persist($group);
                    $this->coreLocator->em()->flush();
                }
            }
        }
    }

    /**
     * To add module.
     *
     * @throws Exception
     */
    private function addModule(object $bundleConfig, array $websites): ?Module
    {
        if ($bundleConfig->module) {
            $roleName = 'ROLE_'.strtoupper($bundleConfig->slug);
            $module = $this->coreLocator->em()->getRepository(Module::class)->findOneBy([
                'role' => $roleName,
            ]);
            if (!$module) {
                $position = count($this->coreLocator->em()->getRepository(Module::class)->findAll()) + 1;
                $module = new Module();
                $module->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $module->setAdminName($bundleConfig->module->name);
                $module->setSlug($bundleConfig->slug);
                $module->setIconClass($bundleConfig->module->icon);
                $module->setRole($roleName);
                $module->setPosition($position);
                $this->coreLocator->em()->persist($module);
                $this->coreLocator->em()->flush();
            }
            foreach ($websites as $website) {
                $configuration = $website->getConfiguration();
                $existingModule = false;
                foreach ($configuration->getModules() as $configModule) {
                    if ($module->getId() === $configModule->getId()) {
                        $existingModule = true;
                        break;
                    }
                }
                if (!$existingModule) {
                    $configuration->addModule($module);
                    $this->coreLocator->em()->persist($configuration);
                    $this->coreLocator->em()->flush();
                }
            }

            return $module;
        }

        return null;
    }

    /**
     * To add blockTypes.
     *
     * @throws Exception
     */
    private function addBlockTypes(object $bundleConfig): array
    {
        $blockTypes = [];
        $position = count($this->coreLocator->em()->getRepository(BlockType::class)->findAll()) + 1;
        foreach ($bundleConfig->blockTypes as $config) {
            $blockType = $this->coreLocator->em()->getRepository(BlockType::class)->findOneBy(['slug' => $config->slug]);
            if (!$blockType) {
                $blockType = new BlockType();
                $blockType->setAdminName($config->title);
                $blockType->setSlug($config->slug);
                $blockType->setCategory('content');
                $blockType->setIconClass($config->icon);
                $blockType->setDropdown(true);
                $blockType->setEditable($config->editable);
                $blockType->setPosition($position);
                $blockType->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $this->coreLocator->em()->persist($blockType);
                $this->coreLocator->em()->flush();
                ++$position;
            }
            $blockTypes[] = $blockType;
        }

        return $blockTypes;
    }

    /**
     * To add module.
     */
    private function addModuleToLayout(array $websites, ?Module $module = null): void
    {
        if ($module) {
            foreach ($websites as $website) {
                $layoutPage = $this->coreLocator->em()->getRepository(LayoutConfiguration::class)->findOneBy([
                    'entity' => Page::class,
                    'website' => $website,
                ]);
                if ($layoutPage) {
                    $existing = false;
                    foreach ($layoutPage->getModules() as $layoutModule) {
                        if ($module->getId() === $layoutModule->getId()) {
                            $existing = true;
                            break;
                        }
                    }
                    if (!$existing) {
                        $layoutPage->addModule($module);
                        $this->coreLocator->em()->persist($layoutPage);
                        $this->coreLocator->em()->flush();
                    }
                }
            }
        }
    }

    /**
     * To add actions.
     *
     * @throws Exception
     */
    private function addActions(object $bundleConfig, ?Module $module = null): void
    {
        if ($bundleConfig->actions && $module) {
            foreach ($bundleConfig->actions as $config) {
                $action = $this->coreLocator->em()->getRepository(Action::class)->findOneBy([
                    'controller' => $config->controller,
                    'entity' => $config->entity,
                    'action' => $config->action,
                    'module' => $module,
                ]);
                if (!$action) {
                    $position = count($this->coreLocator->em()->getRepository(Action::class)->findAll()) + 1;
                    $action = new Action();
                    $action->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                    $action->setAdminName($config->name);
                    $action->setSlug($bundleConfig->slug.'-'.$config->action);
                    $action->setPosition($position);
                    $action->setController($config->controller);
                    $action->setEntity($config->entity);
                    $action->setAction($config->action);
                    $action->setCard($config->card);
                    $action->setDropdown($config->dropdown);
                    $action->setIconClass($config->icon);
                    $action->setModule($module);
                    $this->coreLocator->em()->persist($action);
                    $this->coreLocator->em()->flush();
                }
            }
        }
    }

    /**
     * To add actions.
     *
     * @throws Exception
     */
    private function addLayout(object $bundleConfig, Module $bundleModule, array $websites, array $bundleBlockTypes): void
    {
        foreach ($websites as $website) {
            $position = count($this->coreLocator->em()->getRepository(LayoutConfiguration::class)->findBy(['website' => $website])) + 1;
            foreach ($bundleConfig->layouts as $config) {
                $layoutConfiguration = $this->coreLocator->em()->getRepository(LayoutConfiguration::class)->findOneBy(['website' => $website, 'entity' => $config->classsname]);
                if (!$layoutConfiguration) {
                    $layoutConfiguration = new LayoutConfiguration();
                    $layoutConfiguration->setAdminName($config->title);
                    $layoutConfiguration->setEntity($config->classsname);
                    $layoutConfiguration->setPosition($position);
                    $layoutConfiguration->setWebsite($website);
                    $layoutConfiguration->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                    ++$position;
                    foreach ($bundleBlockTypes as $bundleBlockType) {
                        $layoutConfiguration->addBlockType($bundleBlockType);
                    }
                    foreach ($config->blockTypes as $blockCategory) {
                        $blockTypes = $this->coreLocator->em()->getRepository(BlockType::class)->findBy(['category' => $blockCategory]);
                        foreach ($blockTypes as $blockType) {
                            $layoutConfiguration->addBlockType($blockType);
                        }
                    }
                    $layoutConfiguration->addModule($bundleModule);
                    foreach ($config->modules as $moduleName) {
                        $modules = $this->coreLocator->em()->getRepository(Module::class)->findBy(['role' => 'ROLE_'.strtoupper($moduleName)]);
                        foreach ($modules as $module) {
                            $layoutConfiguration->addModule($module);
                        }
                    }
                    $this->coreLocator->em()->persist($layoutConfiguration);
                    $this->coreLocator->em()->flush();
                }
            }
        }
    }

    /**
     * To check if content need insert.
     */
    private function insertContent(?string $appContent = null, ?string $copyContent = null): bool
    {
        $trimAppContent = '';
        if ($appContent) {
            $trimAppContent = str_replace(["\n", "\r"], '', $appContent);
            $trimAppContent = preg_replace('/\s+/', '', $trimAppContent);
        }
        $trimCopyContent = '';
        if ($copyContent) {
            $trimCopyContent = str_replace(["\n", "\r"], '', $copyContent);
            $trimCopyContent = preg_replace('/\s+/', '', $trimCopyContent);
        }

        return !str_contains($trimAppContent, $trimCopyContent);
    }
}
