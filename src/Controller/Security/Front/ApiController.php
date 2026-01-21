<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ApiController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ApiController extends \App\Controller\Front\FrontController
{
    /**
     * Link to this controller to start the facebook "connect" process.
     */
    #[Route('/security/connect/facebook', name: 'security_front_connect_facebook_start', methods: 'GET', schemes: '%protocol%')]
    public function facebookConnectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('facebook')
            ->redirect(['public_profile', 'email']);
    }

    /**
     * Link to this controller to start the google "connect" process.
     */
    #[Route('/security/connect/google', name: 'security_front_connect_google_start', methods: 'GET', schemes: '%protocol%')]
    public function googleConnectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        //        dd($clientRegistry);
        return new RedirectResponse($this->generateUrl('front_index'));
    }

    /**
     * After going to Facebook, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml.
     */
    #[Route('/security/connect/facebook/check', name: 'security_front_connect_facebook_check', methods: 'GET', schemes: '%protocol%')]
    #[Route('/security/connect/google/check', name: 'security_front_connect_google_check', methods: 'GET', schemes: '%protocol%')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry): Response
    {
        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;

        $message = '';
        if ($request->get('error_message')) {
            $message = $request->get('error_message');
        }

        if ($message) {
            return $this->render('front/'.$websiteTemplate.'/actions/security/api-messages.html.twig', [
                'websiteTemplate' => $websiteTemplate,
                'message' => $message,
            ]);
        }

        return new Response();
    }
}
