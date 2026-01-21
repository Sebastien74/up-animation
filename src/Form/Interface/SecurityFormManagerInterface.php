<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Security\Admin as AdminSecurity;
use App\Form\Manager\Security\Front as FrontSecurity;

/**
 * SecurityFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface SecurityFormManagerInterface
{
    public function adminCompany(): AdminSecurity\CompanyManager;
    public function adminConfirmPassword(): AdminSecurity\ConfirmPasswordManager;
    public function adminGroupPassword(): AdminSecurity\GroupPasswordManager;
    public function adminRegister(): AdminSecurity\RegisterManager;
    public function adminResetPassword(): AdminSecurity\ResetPasswordManager;
    public function adminRole(): AdminSecurity\RoleManager;
    public function adminUser(): AdminSecurity\UserManager;
    public function frontConfirmPassword(): FrontSecurity\ConfirmPasswordManager;
    public function frontProfile(): FrontSecurity\ProfileManager;
    public function frontRegister(): FrontSecurity\RegisterManager;
    public function frontResetPassword(): FrontSecurity\ResetPasswordManager;
    public function frontUser(): FrontSecurity\UserManager;
}