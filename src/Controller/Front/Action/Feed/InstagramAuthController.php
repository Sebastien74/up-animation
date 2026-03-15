<?php

declare(strict_types=1);

namespace App\Controller\Front\Action\Feed;

use App\Entity\Api\Instagram;
use App\Repository\Core\WebsiteRepository;
use App\Service\Content\InstagramService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * InstagramAuthController.
 *
 * Controller for handling Instagram OAuth callback.
 */
class InstagramAuthController extends AbstractController
{
    #[Route('/instagram/callback', name: 'instagram_auth_callback')]
    public function callback(
        Request $request,
        InstagramService $instagramService,
        WebsiteRepository $websiteRepository,
        EntityManagerInterface $entityManager
    ): Response {

        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('error', 'Code d\'autorisation Instagram manquant.');
            return $this->redirectToRoute('admin_website_index'); // Or appropriate admin route
        }

        // In this architecture, we usually have one website, or we can find it by host
        $websiteModel = $websiteRepository->findCurrent();
        $website = $entityManager->getRepository(\App\Entity\Core\Website::class)->find($websiteModel->id);

        if (!$website) {
            $this->addFlash('error', 'Site introuvable.');
            return $this->redirectToRoute('admin_website_index');
        }
        
        $api = $website->getApi();
        if (!$api || !$api->getInstagram()) {
            $this->addFlash('error', 'Configuration Instagram introuvable.');
            return $this->redirectToRoute('admin_website_index');
        }

        /** @var Instagram $instagram */
        $instagram = $api->getInstagram();
        $appId = $instagram->getAppId();
        $appSecret = $instagram->getAppSecret();

        if (!$appId || !$appSecret) {
            $this->addFlash('error', 'App ID ou App Secret Instagram manquant.');
            return $this->redirectToRoute('admin_website_index');
        }

        $longLivedToken = $instagramService->getLongLivedToken($appId, $appSecret, $code);

        if ($longLivedToken) {
            $instagram->setAccessToken($longLivedToken);
            $entityManager->flush();
            $this->addFlash('success', 'Instagram connecté avec succès !');
        } else {
            $this->addFlash('error', 'Impossible de récupérer le token Instagram. Vérifiez vos identifiants d\'application.');
        }

        // Redirect back to the website configuration (API tab if possible)
        return $this->redirectToRoute('admin_website_edit', ['website' => $website->getId(), 'tab' => 'api']);
    }
}
