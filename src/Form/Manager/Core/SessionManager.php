<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Information\Information;
use App\Entity\Media\ThumbConfiguration;
use App\Entity\Seo\SeoConfiguration;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;

/**
 * SessionManager.
 *
 * Set main sessions
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SessionManager::class, 'key' => 'core_session_form_manager'],
])]
class SessionManager
{
    /**
     * Manage Session.
     */
    public function execute(Request $request, mixed $entity): void
    {
        $session = $request->getSession();

        if ($entity instanceof Website) {
            $session->remove('configuration_'.$entity->getId());
        } elseif ($entity instanceof Configuration) {
            $session->remove('website_colors_'.$entity->getId());
            $session->remove('configuration_'.$entity->getWebsite()->getId());
            $session->remove('social_networks_'.$entity->getWebsite()->getId());
        } elseif ($entity instanceof SeoConfiguration || $entity instanceof Information) {
            $session->remove('social_networks_'.$entity->getWebsite()->getId());
        } elseif ($entity instanceof ThumbConfiguration) {
            foreach ($session->all() as $key => $name) {
                if (str_contains($key, 'thumbs_actions_')) {
                    $session->remove($key);
                }
            }
        }
    }
}
