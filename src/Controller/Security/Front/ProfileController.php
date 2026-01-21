<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use App\Controller\Front\FrontController;
use App\Entity\Security\UserFront;
use App\Form\Manager\Security\Front\ProfileManager;
use App\Form\Type\Security\Front\UserFrontType;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Random\RandomException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ProfileController.
 *
 * FrontUser Profile management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_USER_FRONT')]
class ProfileController extends FrontController
{
    /**
     * Show UserFront Profile.
     *
     * @throws \Exception
     */
    #[Route([
        'fr' => '/mon-espace-personnel/mon-profil',
        'en' => '/my-personal-space/my-profile',
        'es' => '/mi-espacio-personal/mi-perfil',
        'it' => '/mio-spazio-personale/il-mio-profilo',
    ], name: 'security_front_profile', methods: 'GET', schemes: '%protocol%', priority: 1)]
    public function show(Request $request, ProfileManager $manager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof UserFront) {
            return $this->redirectToRoute('security_front_forms');
        }

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $this->synchronize($user, $manager);

        return $this->render('front/'.$websiteTemplate.'/actions/security/back/profile-show.html.twig', array_merge([
            'isSecure' => true,
            'disableMicrodata' => true,
            'user' => $user,
        ], $this->defaultArgs($website)));
    }

    /**
     * Edit UserFront Profile.
     *
     * @throws RandomException
     */
    #[Route([
        'fr' => '/mon-espace-personnel/mon-profil/edition',
        'en' => '/my-personal-space/my-profile/edit',
        'es' => '/mi-espacio-personal/mi-perfil/edicion',
        'it' => '/mio-spazio-personale/il-mio-profilo/la-modifica',
    ], name: 'security_front_profile_edit', methods: 'GET|POST', schemes: '%protocol%', priority: 1)]
    public function edit(Request $request, ProfileManager $manager): JsonResponse|Response
    {
        $user = $this->getUser();
        if (!$user instanceof UserFront) {
            return $this->redirectToRoute('security_front_forms');
        }

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $template = 'front/'.$websiteTemplate.'/actions/security/back/profile-edit.html.twig';
        $this->synchronize($user, $manager);
        $userRequest = $manager->setUserRequest($user);
        $requestAlreadySend = is_numeric($userRequest->getId());

        $form = !$requestAlreadySend ? $this->createForm(UserFrontType::class, $userRequest) : false;
        if (!$requestAlreadySend) {
            $form->handleRequest($request);
        }

        $arguments = array_merge([
            'isSecure' => true,
            'disableMicrodata' => true,
            'redirection' => false,
            'user' => $user,
            'userRequest' => $userRequest,
            'requestAlreadySend' => $requestAlreadySend,
            'form' => !$requestAlreadySend ? $form->createView() : null,
        ], $this->defaultArgs($website));

        $redirection = false;
        if (!$requestAlreadySend && $form->isSubmitted()) {
            if ($form->isValid()) {
                $manager->execute($form);
                $redirection = $this->generateUrl('security_front_profile_edit');
            }
        }

        return $request->get('ajax') ? new JsonResponse([
            'success' => $form && $form->isValid(),
            'redirection' => $form && $form->isValid() ? $redirection : false,
            'html' => $this->renderView($template, $arguments),
        ]) :$this->render($template, $arguments);
    }

    /**
     * To remove UserFront.
     *
     * @throws NonUniqueResultException|MappingException|RandomException
     */
    #[Route([
        'fr' => '/mon-espace-personnel/mon-profil/remove-request',
        'en' => '/my-personal-space/my-profile/remove-request',
        'es' => '/mi-espacio-personal/mi-perfil/remove-request',
        'it' => '/mio-spazio-personale/il-mio-profilo/remove-request',
    ], name: 'security_front_profile_remove_request', methods: 'GET', schemes: '%protocol%', priority: 1)]
    public function removeRequest(ProfileManager $manager): JsonResponse|Response
    {
        $manager->removeRequest($this->coreLocator->user());
        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;

        return $this->render('front/'.$websiteTemplate.'/actions/security/back/request.html.twig', array_merge([
            'type' => 'remove',
            'user' => $this->coreLocator->user(),
            'title' => $this->coreLocator->translator()->trans('Demande de suppression de votre compte', [], 'security_cms'),
        ], $this->defaultArgs($website)));
    }

    /**
     * To remove UserFront.
     */
    #[Route([
        'fr' => '/mon-espace-personnel/mon-profil/remove/{token}',
        'en' => '/my-personal-space/my-profile/remove/{token}',
        'es' => '/mi-espacio-personal/mi-perfil/remove/{token}',
        'it' => '/mio-spazio-personale/il-mio-profilo/remove/{token}',
    ], name: 'security_front_user_remove_request', methods: 'GET', schemes: '%protocol%', priority: 1)]
    public function remove(Request $request, string $token): JsonResponse|Response
    {
        /** @var UserFront $currentUser */
        $currentUser = $this->coreLocator->user();
        $user = $this->coreLocator->em()->getRepository(UserFront::class)->findOneBy(['tokenRemoveRequest' => urldecode($token)]);
        if ($user && $user->getId() !== $currentUser->getId()) {
            return $this->redirectToRoute('front_index');
        }

        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $status = $request->get('status');

        if ('confirm' === $status) {
            $this->coreLocator->em()->remove($user);
            $this->coreLocator->em()->flush();
            return $this->redirectToRoute('security_front_user_remove');
        }

        return $this->render('front/'.$websiteTemplate.'/actions/security/back/request.html.twig', array_merge([
            'type' => 'remove-confirm',
            'user' => $user,
            'title' => $this->coreLocator->translator()->trans('Demande de suppression de votre compte', [], 'security_cms'),
        ], $this->defaultArgs($website)));
    }

    /**
     * Synchronize UserFront data.
     */
    private function synchronize(UserFront $user, ProfileManager $manager): void
    {
        $manager->synchronize($user);
    }
}
