<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Form\Manager\Security\Admin\ConfirmPasswordManager;
use App\Form\Manager\Security\Admin\ResetPasswordManager;
use App\Form\Type\Security\Admin\PasswordRequestType;
use App\Form\Type\Security\Admin\PasswordResetType;
use App\Repository\Security\UserRepository;
use App\Security\BaseAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * ResetPasswordController.
 *
 * Security reset password management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/secure/user/reset-password/{_locale}', schemes: '%protocol%')]
class ResetPasswordController extends AdminController
{
    /**
     * Request password.
     *
     * @throws \Exception
     */
    #[Route('/request', name: 'security_password_request', methods: 'GET|POST')]
    public function request(Request $request, BaseAuthenticator $baseAuthenticator, ResetPasswordManager $manager)
    {
        $website = $this->getWebsite();
        $form = $this->createForm(PasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $baseAuthenticator->checkRecaptcha($website, $request, true)) {
            $manager->send($form->getData());

            return $this->redirectToRoute('security_password_request');
        }

        if ($request->get('expire')) {
            $session = new Session();
            $session->getFlashBag()->add('warning', $this->coreLocator->translator()->trans('Votre mot de passe a expiré, vous devez le réinitialiser.', [], 'security_cms'));
        }

        return $this->adminRender('security/password-request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Reset password.
     *
     * @throws \Exception
     */
    #[Route('/confirm/{token}', name: 'security_password_confirm', methods: 'GET|POST')]
    public function confirm(
        Request $request,
        string $token,
        UserRepository $repository,
        BaseAuthenticator $baseAuthenticator,
        ConfirmPasswordManager $manager,
        AuthorizationCheckerInterface $authorizationChecker,
    ) {
        $website = $this->getWebsite();
        $user = $repository->findOneBy(['tokenRequest' => urldecode($token)]);

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $baseAuthenticator->checkRecaptcha($website, $request, true)) {
            $manager->confirm($form->getData(), $user);
            $this->addFlash('success', $this->coreLocator->translator()->trans('Votre mot de passe a été modifié avec succès.', [], 'security_cms'));

            return $this->redirectToRoute('security_login');
        }

        if (!$user && $authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard', ['website' => $this->getWebsite()->id]);
        } elseif (!$user) {
            throw $this->createAccessDeniedException($this->coreLocator->translator()->trans('Accès refusé.', [], 'security_cms'));
        }

        return $this->adminRender('security/password-confirm.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
