<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Front\FrontController;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Form\Manager\Security\Admin\RegisterManager;
use App\Form\Type\Security\Admin\LoginType;
use App\Form\Type\Security\Admin\RegistrationType;
use App\Repository\Core\WebsiteRepository;
use App\Security\BaseAuthenticator;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * SecurityController.
 *
 * Main security controller to manage auth User
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/secure/user/{_locale}/%security_token%', schemes: '%protocol%')]
class SecurityController extends FrontController
{
    /**
     * Login page.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|Exception
     */
    #[Route([
        'fr' => '/login',
        'en' => '/login',
    ], name: 'security_login', methods: 'GET|POST', priority: 300)]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, WebsiteRepository $websiteRepository): Response
    {
        $website = $websiteRepository->findCurrent();
        $securityKey = $website->entity->getSecurity()->getSecurityKey();
        if (!$securityKey) {
            $this->setSecurityKey($website->entity);
        }

        if ($this->getUser() instanceof User && $this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard', [
                'website' => $website->id,
            ]);
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'website' => $website,
            'securityKey' => $securityKey,
            'login_type' => $_ENV['SECURITY_ADMIN_LOGIN_TYPE'],
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Register page.
     *
     * @throws NonUniqueResultException|InvalidArgumentException|Exception
     */
    #[Route('/register', name: 'security_register', methods: 'GET|POST')]
    public function register(
        Request $request,
        WebsiteRepository $websiteRepository,
        BaseAuthenticator $baseAuthenticator,
        RegisterManager $manager,
    ): RedirectResponse|string|Response|null {
        $website = $websiteRepository->findOneByHost($request->getHost());
        $security = $website->entity->getSecurity();

        if (!$security->isAdminRegistration()) {
            return $this->redirectToRoute('front_index');
        }

        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $baseAuthenticator->checkRecaptcha($website, $request, true)) {
            return $manager->register($form->getData(), $security, $website);
        }

        if ($request->get('validation')) {
            $session = new Session();
            $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans("Merci pour inscription. Votre compte dois être validé par l'administrateur.", [], 'security_cms'));
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
            'website' => $website,
        ]);
    }

    /**
     * Account ApiModel.
     */
    #[Route('/security/json', name: 'security_json', methods: 'GET')]
    public function accountApi(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json($user, 200, [], [
            'groups' => 'main',
        ]);
    }

    /**
     * Set WebsiteModel Security key.
     *
     * @throws Exception
     */
    private function setSecurityKey(Website $website): void
    {
        $security = $website->getSecurity();
        $security->setSecurityKey($this->coreLocator->alphanumericKey(30));
        $this->coreLocator->em()->persist($security);
        $this->coreLocator->em()->flush();
        $this->coreLocator->em()->refresh($security);
    }
}
