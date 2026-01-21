<?php

declare(strict_types=1);

namespace App\Form\Model\Security\Front;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProfileRegistrationModel.
 *
 * Set UserFront Profile security asserts form attributes for registration
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProfileRegistrationModel
{
    #[Assert\NotBlank(['message' => 'Veuillez sélectionner un genre.'])]
    protected ?string $gender = null;

    public function getGender(): ?string
    {
        return $this->gender;
    }
}
