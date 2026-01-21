<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Module\Form\Form;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * FormDuplicateInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface FormDuplicateInterface
{
    public function execute(Form $form, UserInterface $user): Form;
}