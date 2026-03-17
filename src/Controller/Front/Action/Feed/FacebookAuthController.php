<?php

declare(strict_types=1);

namespace App\Controller\Front\Action\Feed;

use App\Entity\Api\Facebook;
use App\Repository\Core\WebsiteRepository;
use App\Service\Content\FacebookService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * FacebookAuthController.
 *
 * Controller for handling Facebook OAuth callback.
 */
class FacebookAuthController extends AbstractController
{
    #[Route('/facebook/callback', name: 'facebook_auth_callback')]
    public function callback(
        Request $request,
        FacebookService $facebookService,
        WebsiteRepository $websiteRepository,
        EntityManagerInterface $entityManager
    ): Response {

        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('error', 'Code d\'autorisation Facebook manquant.');
            return $this->redirectToRoute('admin_website_index');
        }

        $websiteModel = $websiteRepository->findCurrent();
        $website = $entityManager->getRepository(\App\Entity\Core\Website::class)->find($websiteModel->id);

        if (!$website) {
            $this->addFlash('error', 'Site introuvable.');
            return $this->redirectToRoute('admin_website_index');
        }
        
        $api = $website->getApi();
        if (!$api || !$api->getFacebook()) {
            $this->addFlash('error', 'Configuration Facebook introuvable.');
            return $this->redirectToRoute('admin_website_index');
        }

        /** @var Facebook $facebook */
        $facebook = $api->getFacebook();
        $appId = $facebook->getAppId();
        $appSecret = $facebook->getApiSecretKey();
        $pageId = $facebook->getPageId();

        if (!$appId || !$appSecret || !$pageId) {
            $this->addFlash('error', 'App ID, App Secret ou Page ID Facebook manquant.');
            return $this->redirectToRoute('admin_website_index');
        }

        $pageToken = $facebookService->getPageAccessToken($appId, $appSecret, $pageId, $code);

        if ($pageToken) {
            $facebook->setAccessToken($pageToken);
            $entityManager->flush();
            $this->addFlash('success', 'Facebook connecté avec succès !');
        } else {
            $this->addFlash('error', 'Impossible de récupérer le token de page Facebook. Vérifiez vos identifiants d\'application et l\'ID de la page.');
        }

        return $this->redirectToRoute('admin_website_edit', ['website' => $website->getId(), 'tab' => 'api']);
    }
}
