<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Module\Newsletter\Email;
use App\Form\Manager\Front\NewsletterManager;
use App\Form\Type\Module\Newsletter\FrontType;
use App\Model\EntityModel;
use App\Repository\Module\Newsletter\CampaignRepository;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * NewsletterController.
 *
 * Front Newsletter renders.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewsletterController extends FrontController
{
    /**
     * View.
     *
     * @throws Exception|InvalidArgumentException
     */
    #[Route('/front/newsletter/view/{filter}', name: 'front_newsletter_view', options: ['isMainRequest' => false], methods: 'GET|POST', schemes: '%protocol%')]
    public function view(
        Request $request,
        CampaignRepository $campaignRepository,
        NewsletterManager $manager,
        mixed $filter = null): JsonResponse|Response
    {
        if (!$filter) {
            return new Response();
        }

        $block = $request->get('block') instanceof Block ? $request->get('block') : null;
        if ('GET' === $request->getMethod()) {
            $cache = $this->coreLocator->cacheService()->cachePool($block, 'newsletter_view', 'GET');
            if ($cache) {
                return $cache;
            }
        }

        $website = $this->getWebsite();
        $campaign = $campaignRepository->findOneByFilter($website->entity, $request->getLocale(), $filter);
        if (!$campaign) {
            return new Response();
        }

        $configuration = $website->configuration;
        $template = $configuration->template;
        $form = $this->createForm(FrontType::class, new Email(), ['form_data' => $campaign]);
        $form->handleRequest($request);
        $arguments = [
            'configuration' => $configuration,
            'websiteTemplate' => $template,
            'website' => $website,
            'campaign' => $campaign,
            'form' => $form->createView(),
        ];

        if ($form->isSubmitted()) {
            $response = $manager->execute($form, $campaign, $form->getData());
            return new JsonResponse([
                'success' => $response instanceof Email,
                'redirection' => $response instanceof Email ? $this->generateUrl('front_newsletter_thanks', ['token' => $response->getToken()]) : false,
                'html' => $this->renderView('front/'.$template.'/actions/newsletter/view.html.twig', $arguments),
            ]);
        }

        $response = $this->cache($request, 'front/'.$template.'/actions/newsletter/view.html.twig', $campaign, $arguments);

        return $this->coreLocator->cacheService()->cachePool($block, 'newsletter_view', 'GENERATE', $response);
    }

    /**
     * Thanks.
     *
     * @throws Exception
     */
    #[Route([
        'fr' => '/newsletter/merci/{token}',
        'en' => '/newsletter/thanks/{token}',
    ], name: 'front_newsletter_thanks', methods: 'GET', schemes: '%protocol%', priority: 300)]
    public function thanks(string $token): JsonResponse|Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $template = $configuration->template;
        $email = $this->coreLocator->em()->getRepository(Email::class)->findOneBy(['token' => $token]);

        if (!$email) {
            return $this->redirectToRoute('front_index');
        }

        return $this->render('front/'.$template.'/actions/newsletter/thanks.html.twig', array_merge([
            'email' => $email,
            'campaign' => EntityModel::fromEntity($email->getCampaign(), $this->coreLocator)->response,
            'websiteTemplate' => $template,
        ], $this->defaultArgs($website)));
    }

    /**
     * Confirmation.
     *
     * @throws Exception
     */
    #[Route([
        'fr' => '/newsletter/confirmation/{token}/{status}',
        'en' => '/newsletter/confirmation/{token}/{status}',
    ], name: 'front_newsletter_confirmation', defaults: ['status' => null], methods: 'GET', schemes: '%protocol%', priority: 300)]
    public function confirmation(NewsletterManager $manager, string $token, ?string $status = null): JsonResponse|Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $template = $configuration->template;
        $email = $this->coreLocator->em()->getRepository(Email::class)->findOneBy(['token' => $token]);

        if (!$email) {
            return $this->redirectToRoute('front_index');
        }

        $status = $manager->confirmation($email, $status);

        return $this->render('front/'.$template.'/actions/newsletter/confirmation.html.twig', array_merge([
            'email' => $email,
            'status' => $status,
            'websiteTemplate' => $template,
        ], $this->defaultArgs($website)));
    }
}
