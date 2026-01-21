<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use App\Controller\Front\FrontController;
use App\Form\Manager\Security\Front\ConfirmPasswordManager;
use App\Form\Manager\Security\Front\ResetPasswordManager;
use App\Form\Type\Security\Front\PasswordRequestType;
use App\Form\Type\Security\Front\PasswordResetType;
use App\Security\BaseAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ResetPasswordController.
 *
 * Security reset password management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ResetPasswordController extends FrontController
{
    /**
     * Request password.
     *
     * @throws \Exception
     */
    #[Route([
        'fr' => '/espace-personnel/modification-mot-de-passe',
        'en' => '/personal-space/password-change',
        'es' => '/espacio-personal/cambio-de-contrasena',
        'it' => '/spazio-personale/cambio-password',
    ], name: 'security_front_password_request', methods: 'GET|POST', schemes: '%protocol%')]
    public function request(Request $request, ResetPasswordManager $manager, BaseAuthenticator $baseAuthenticator): JsonResponse|Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        $template = 'front/'.$websiteTemplate.'/actions/security/front/password-request.html.twig';
        $form = $this->createForm(PasswordRequestType::class);
        $form->handleRequest($request);

        $arguments = array_merge([
            'templateName' => 'security-front',
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ], $this->defaultArgs($website));

        if ($form->isSubmitted()) {
            $isValid = $form->isValid() && $baseAuthenticator->checkRecaptcha($website, $request, true);
            if ($isValid) {
                $arguments['formRequest'] = $manager->send($form->getData(), $website->entity);
                $arguments['user'] = !empty($arguments['formRequest']['user']) ? $arguments['formRequest']['user'] : $this->getUser();
                $isValid = $arguments['formRequest']['valid'];
            }

            return new JsonResponse(['success' => $isValid, 'html' => $this->renderView($template, $arguments)]);
        }

        return $this->render($template, $arguments);
    }

    /**
     * To send back email.
     *
     * @throws \Exception
     */
    #[Route([
        'fr' => '/espace-personnel/modification-mot-de-passe/e-mail/{token}',
        'en' => '/personal-space/password-change/e-mail/{token}',
        'es' => '/espacio-personal/cambio-de-contrasena/e-mail/{token}',
        'it' => '/spazio-personale/cambio-password/correo-electronico/{token}',
    ], name: 'security_front_password_request_email', methods: 'GET|POST', schemes: '%protocol%')]
    public function sendBackEmail(
        ConfirmPasswordManager $confirmPasswordManager,
        ResetPasswordManager $resetPasswordManager,
        string $token,
    ): Response {

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $user = $confirmPasswordManager->checkUser(urldecode($token));

        if (!$user) {
            return $this->redirectToRoute('front_index');
        }

        $sendMail = !$user->getTokenRequest() instanceof \DateTime;
        if (!$user->getTokenRequest() instanceof \DateTime) {
            $resetPasswordManager->sendEmail($user, $user->getEmail(), $user->getTokenRequest(), $website->entity);
        }

        return $this->render('front/'.$websiteTemplate.'/actions/security/front/password-request-send-back.html.twig', array_merge([
            'sendMail' => $sendMail,
            'templateName' => 'security-front',
        ], $this->defaultArgs($website)));
    }

    /**
     * Reset password.
     *
     * @throws \Exception
     */
    #[Route([
        'fr' => '/espace-personnel/mot-de-passe/reinitialisation/{token}',
        'en' => '/personal-space/password/reset/{token}',
        'es' => '/espacio-personal/contrasena/reajustar/{token}',
        'it' => '/spazio-personale/password/reset/{token}',
    ], name: 'security_front_password_confirm', methods: 'GET|POST', schemes: '%protocol%')]
    public function confirm(
        Request $request,
        string $token,
        ConfirmPasswordManager $manager,
        BaseAuthenticator $baseAuthenticator,
    ): \Symfony\Component\HttpFoundation\RedirectResponse|JsonResponse|Response {

        $user = $manager->checkUser(urldecode($token));

        if (!$user) {
            return $this->redirectToRoute('front_index');
        }

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $template = 'front/'.$websiteTemplate.'/actions/security/front/password-confirm.html.twig';
        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        $arguments = array_merge([
            'templateName' => 'security-front',
            'valid' => false,
            'form' => $form->createView(),
        ], $this->defaultArgs($website));

        if ($form->isSubmitted()) {
            $isValid = $form->isValid() && $baseAuthenticator->checkRecaptcha($website, $request, true);
            if ($isValid) {
                $manager->confirm($form->getData(), $user);
                $arguments['valid'] = true;
            }

            return new JsonResponse(['success' => $isValid, 'html' => $this->renderView($template, $arguments)]);
        }

        return $this->render($template, $arguments);
    }
}
