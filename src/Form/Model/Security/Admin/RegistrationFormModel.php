<?php

declare(strict_types=1);

namespace App\Form\Model\Security\Admin;

use App\Form\Validator\UniqUserEmail;
use App\Form\Validator\UniqUserLogin;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * RegistrationFormModel.
 *
 * Set User security asserts form attributes for registration
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RegistrationFormModel
{
    #[Assert\NotBlank(['message' => 'Veuillez saisir un identifiant.'])]
    #[UniqUserLogin]
    public ?string $login = null;

    #[Assert\NotBlank(['message' => 'Veuillez saisir un email.'])]
    #[Assert\Email]
    #[UniqUserEmail]
    public ?string $email = null;

    #[Assert\NotBlank(['message' => 'Veuillez saisir votre nom.'])]
    public ?string $lastName = null;

    #[Assert\NotBlank(['message' => 'Veuillez saisir votre prénom.'])]
    public ?string $firstName = null;

    #[Assert\NotBlank(['message' => 'Veuillez saisir un mot de passe.'])]
    #[Assert\Regex([
        'message' => 'Le mot de passe doit comporter au moins 8 caractères, contenir au moins un chiffre, une majuscule et une minuscule.',
        'pattern' => '/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{8,}/',
    ])]
    public ?string $plainPassword = null;

    #[Assert\IsTrue(['message' => 'Vous devez accepter les conditions générales.'])]
    public bool $agreeTerms = false;

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function getAgreeTerms(): bool
    {
        return $this->agreeTerms;
    }
}
