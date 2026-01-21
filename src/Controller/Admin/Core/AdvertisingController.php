<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Module;
use App\Entity\Layout\BlockType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Encoder\JsonDecode;

/**
 * AdvertisingController.
 *
 * Advertising management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/support', schemes: '%protocol%')]
class AdvertisingController extends AdminController
{
    /**
     * Display extensions view.
     */
    #[Route('/extensions', name: 'admin_advertising', methods: 'GET')]
    public function extensions(Request $request): Response
    {
        $extensions = [];
        $configuration = $this->getWebsite()->configuration->entity;

        $activesBlocksTypes = [];
        $blocksTypes = $this->coreLocator->em()->getRepository(BlockType::class)->findBy([], ['position' => 'ASC']);
        foreach ($configuration->getBlockTypes() as $blockType) {
            $activesBlocksTypes[] = $blockType->getSlug();
        }

        foreach ($blocksTypes as $key => $blockType) {
            $extensions[$key]['active'] = in_array($blockType->getSlug(), $activesBlocksTypes);
            $extensions[$key]['entity'] = $blockType;
        }

        $activesModules = [];
        $modules = $this->coreLocator->em()->getRepository(Module::class)->findBy([], ['position' => 'ASC']);
        $modules = $this->setExternalModules($request, $modules);
        foreach ($configuration->getModules() as $module) {
            $activesModules[] = $module->getSlug();
        }
        foreach ($modules as $key => $module) {
            $extensions[$key]['active'] = in_array($module->getSlug(), $activesModules);
            $extensions[$key]['entity'] = $module;
        }

        parent::breadcrumb($request, []);

        return $this->render('admin/page/core/extensions.html.twig', array_merge($this->arguments, [
            'extensions' => $extensions,
        ]));
    }

    /**
     * To set externals modules.
     */
    private function setExternalModules(Request $request, array $modules): array
    {
        $website = $this->getWebsite();
        $position = count($modules) + 1;
        $file = new File($this->coreLocator->projectDir().'\bin\data\fixtures\extensions.json');
        $jsonDecoder = new JsonDecode();
        $externalModules = $jsonDecoder->decode($file->getContent(), 'json');

        foreach ($externalModules as $slug => $config) {
            $existing = false;
            foreach ($modules as $module) {
                if ($slug === $module->getSlug()) {
                    $existing = true;
                    break;
                }
            }
            if (!$existing) {
                $module = new Module();
                $module->setAdminName($config->title);
                $module->setSlug($slug);
                $module->setPosition($position);
                $module->setIconClass($config->icon);
                $modules[] = $module;
                ++$position;
            }
            if (!empty($module)) {
                $module->setInAdvert(true);
                if ($module->getIntls()->isEmpty()) {
                    foreach ($website->configuration->allLocales as $locale) {
                        $intlData = $this->coreLocator->metadata($module, 'intls');
                        $intl = new ($intlData->targetEntity)();
                        $intl->setLocale($locale);
                        $intl->setWebsite($website->entity);
                        $module->addIntl($intl);
                    }
                }
                foreach ($module->getIntls() as $intl) {
                    if (!$intl->getTitle()) {
                        $intl->setTitle($config->title);
                    }
                    if (!$intl->getBody()) {
                        $intl->setBody($config->body);
                    }
                }
            }
        }

        return $modules;
    }
}
