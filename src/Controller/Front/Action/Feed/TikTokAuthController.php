<?php

declare(strict_types=1);

namespace App\Controller\Front\Action\Feed;

use App\Entity\Api\TikTok;
use App\Repository\Core\WebsiteRepository;
use App\Service\Content\TikTokService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * TikTokAuthController.
 *
 * Controller for handling TikTok OAuth callback.
 */
class TikTokAuthController extends AbstractController
{
    #[Route('/tiktok/callback', name: 'tiktok_auth_callback')]
    public function callback(
        Request $request,
        TikTokService $tiktokService,
        WebsiteRepository $websiteRepository,
        EntityManagerInterface $entityManager
    ): Response {

        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('error', 'Code d\'autorisation TikTok manquant.');
            return $this->redirectToRoute('admin_website_index');
        }

        $websiteModel = $websiteRepository->findCurrent();
        $website = $entityManager->getRepository(\App\Entity\Core\Website::class)->find($websiteModel->id);

        if (!$website) {
            $this->addFlash('error', 'Site introuvable.');
            return $this->redirectToRoute('admin_website_index');
        }
        
        $api = $website->getApi();
        if (!$api || !$api->getTikTok()) {
            $this->addFlash('error', 'Configuration TikTok introuvable.');
            return $this->redirectToRoute('admin_website_index');
        }

        /** @var TikTok $tiktok */
        $tiktok = $api->getTikTok();
        $appId = $tiktok->getAppId();
        $appSecret = $tiktok->getAppSecret();

        if (!$appId || !$appSecret) {
            $this->addFlash('error', 'Client Key ou Client Secret TikTok manquant.');
            return $this->redirectToRoute('admin_website_index');
        }

        $accessToken = $tiktokService->getAccessToken($appId, $appSecret, $code);

        if ($accessToken) {
            $tiktok->setAccessToken($accessToken);
            $entityManager->flush();
            $this->addFlash('success', 'TikTok connecté avec succès !');
        } else {
            $this->addFlash('error', 'Impossible de récupérer le token TikTok. Vérifiez vos identifiants d\'application.');
        }

        return $this->redirectToRoute('admin_website_edit', ['website' => $website->getId(), 'tab' => 'api']);
    }
}
