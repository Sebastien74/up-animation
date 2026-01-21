<?php

declare(strict_types=1);

namespace App\Form\Manager\Security\Front;

use App\Entity\Information\Address;
use App\Entity\Security\Profile;
use App\Entity\Security\UserFront;
use App\Entity\Security\UserRequest;
use App\Form\Manager\Security\PictureManager;
use App\Service\Core\MailerService;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Random\RandomException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * ProfileManager.
 *
 * Manage UserFront Profile
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ProfileManager::class, 'key' => 'security_front_profile_form_manager'],
])]
class ProfileManager extends BaseManager
{
    private ?string $addressConfiguration;
    private Profile $profile;

    /**
     * ProfileManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly PictureManager $pictureManager,
        private readonly MailerService $mailer,
    ) {
        $this->addressConfiguration = $_ENV['SECURITY_FRONT_ADDRESSES'];
        parent::__construct($coreLocator, $this->mailer);
    }

    /**
     * To synchronize all UserFront data.
     */
    public function synchronize(UserFront $user): void
    {
        $this->synchronizeProfile($user);
        $this->synchronizeAddresses($user);
    }

    /**
     * To execute process.
     *
     * @throws RandomException|Exception
     */
    public function execute(FormInterface $form): void
    {
        /** @var UserRequest $userRequest */
        $userRequest = $form->getData();
        $user = $userRequest->getUserFront();

        $isUpdated = false;
        $excluded = ['token'];
        $metadata = $this->coreLocator->em()->getClassMetadata(get_class($userRequest));
        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            $getter = 'get' . ucfirst($fieldName);
            if ('string' === $mapping['type'] && !in_array($fieldName, $excluded, true) && $userRequest->$getter() !== $user->$getter()) {
                $isUpdated = true;
                break;
            }
        }

        if ($isUpdated) {

            $userRequest->setToken($this->token($userRequest->getEmail()));
            $userRequest->setTokenDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));

//        $this->pictureManager->execute($user, $form);
            $this->coreLocator->em()->persist($userRequest);
            $this->coreLocator->em()->flush();

            $subject = $this->coreLocator->translator()->trans('Modification de vos données personnelles', [], 'security_cms');
            $message = $this->message('front-email-confirmation', $subject);
            $this->sendMail($user, $message,'profile-request');

            $session = $this->coreLocator->request()->getSession();
            $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans('Un email de confirmation vous a été envoyé à votre adresse actuelle. Pour des raisons de sécurité, ces modifications ne seront pas appliquées tant que vous ne les aurez pas validées depuis ce message.', [], 'front'));
        }
    }

    /**
     * To set User Request.
     */
    public function setUserRequest(UserFront $user): ?UserRequest
    {
        $userRequest = $user->getUserRequest();

        if (!$user->getUserRequest()) {
            $userRequest = new UserRequest();
            $userRequest->setLogin($user->getLogin());
            $userRequest->setEmail($user->getEmail());
            $userRequest->setLastName($user->getLastName());
            $userRequest->setFirstName($user->getFirstName());
            $userRequest->setLocale($user->getLocale());
            $userRequest->setUserFront($user);
        }

        return $userRequest;
    }

    /**
     * To send remove User Request.
     *
     * @throws NonUniqueResultException|MappingException|RandomException|Exception
     */
    public function removeRequest(UserInterface $user): void
    {
        /** @var UserFront $user */
        $user->setTokenRemoveRequest($this->token($user->getEmail()));
        $user->setTokenRemoveRequestDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $this->coreLocator->em()->persist($user);
        $this->coreLocator->em()->flush();

        $subject = $this->coreLocator->translator()->trans('Demande de suppression de votre compte', [], 'security_cms');
        $message = $this->message('front-email-remove', $subject);
        $this->sendMail($user, $message,'remove-request');
    }

    /**
     * To confirm User Request.
     */
    public function confirmUserRequest(UserRequest $userRequest): ?UserRequest
    {
        $excluded = ['token'];
        $metadata = $this->coreLocator->em()->getClassMetadata(get_class($userRequest));
        $user = $userRequest->getUserFront();
        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            $getter = 'get' . ucfirst($fieldName);
            if ('string' === $mapping['type'] && !in_array($fieldName, $excluded, true) && $userRequest->$getter() !== $user->$getter()) {
                $setter = 'set' . ucfirst($fieldName);
                $user->$setter($userRequest->$getter());
            }
        }
        $user->setUserRequest(null);
        $this->coreLocator->em()->remove($userRequest);
        $this->coreLocator->em()->persist($user);
        $this->coreLocator->em()->flush();

        return $userRequest;
    }

    /**
     * To synchronize Addresses.
     */
    private function synchronizeProfile(UserFront $user): void
    {
        if (!$user->getProfile() instanceof Profile) {
            $profile = new Profile();
            $profile->setUserFront($user);
            $user->setProfile($profile);
        }
        $this->profile = $user->getProfile();
    }

    /**
     * To synchronize Addresses.
     */
    private function synchronizeAddresses(UserFront $user): void
    {
        if ($this->addressConfiguration) {
            $this->synchronizeAddress($user, 'basic', 1);
            if ('full' === $this->addressConfiguration) {
                $this->synchronizeAddress($user, 'billing', 2);
            }
        }
    }

    /**
     * To synchronize Address.
     */
    private function synchronizeAddress(UserFront $user, string $slug, int $position): void
    {
        if ($this->addressConfiguration) {
            $repository = $this->coreLocator->em()->getRepository(Profile::class);
            $existing = $repository->addressExist($user, $slug);
            if (!$existing) {
                $address = new Address();
                $address->setPosition($position);
                $address->setSlug($slug);
                $this->profile->addAddress($address);
                $this->coreLocator->em()->persist($this->profile);
                $this->coreLocator->em()->flush();
            }
        }
    }
}
