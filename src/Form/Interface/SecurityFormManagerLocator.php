<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Security\Admin as AdminSecurity;
use App\Form\Manager\Security\Front as FrontSecurity;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * SecurityFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SecurityFormManagerLocator implements SecurityFormManagerInterface
{
    /**
     * ApiFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(AdminSecurity\CompanyManager::class, indexAttribute: 'key')] protected ServiceLocator $adminCompanyLocator,
        #[AutowireLocator(AdminSecurity\ConfirmPasswordManager::class, indexAttribute: 'key')] protected ServiceLocator $adminConfirmPasswordLocator,
        #[AutowireLocator(AdminSecurity\GroupPasswordManager::class, indexAttribute: 'key')] protected ServiceLocator $adminGroupPasswordLocator,
        #[AutowireLocator(AdminSecurity\RegisterManager::class, indexAttribute: 'key')] protected ServiceLocator $adminRegisterLocator,
        #[AutowireLocator(AdminSecurity\ResetPasswordManager::class, indexAttribute: 'key')] protected ServiceLocator $adminResetPasswordLocator,
        #[AutowireLocator(AdminSecurity\RoleManager::class, indexAttribute: 'key')] protected ServiceLocator $adminRoleLocator,
        #[AutowireLocator(AdminSecurity\UserManager::class, indexAttribute: 'key')] protected ServiceLocator $adminUserLocator,
        #[AutowireLocator(FrontSecurity\ConfirmPasswordManager::class, indexAttribute: 'key')] protected ServiceLocator $frontConfirmPasswordLocator,
        #[AutowireLocator(FrontSecurity\ProfileManager::class, indexAttribute: 'key')] protected ServiceLocator $frontProfileLocator,
        #[AutowireLocator(FrontSecurity\RegisterManager::class, indexAttribute: 'key')] protected ServiceLocator $frontRegisterLocator,
        #[AutowireLocator(FrontSecurity\ResetPasswordManager::class, indexAttribute: 'key')] protected ServiceLocator $frontResetPasswordLocator,
        #[AutowireLocator(FrontSecurity\UserManager::class, indexAttribute: 'key')] protected ServiceLocator $frontUserLocator,
    ) {
    }

    /**
     * To get AdminSecurity\RoleManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function adminCompany(): AdminSecurity\CompanyManager
    {
        return $this->adminCompanyLocator->get('security_admin_company_form_manager');
    }

    /**
     * To get AdminSecurity\ConfirmPasswordManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function adminConfirmPassword(): AdminSecurity\ConfirmPasswordManager
    {
        return $this->adminConfirmPasswordLocator->get('security_admin_confirm_password_form_manager');
    }

    /**
     * To get AdminSecurity\GroupPasswordManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function adminGroupPassword(): AdminSecurity\GroupPasswordManager
    {
        return $this->adminGroupPasswordLocator->get('security_admin_group_password_form_manager');
    }

    /**
     * To get AdminSecurity\RegisterManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function adminRegister(): AdminSecurity\RegisterManager
    {
        return $this->adminRegisterLocator->get('security_admin_register_form_manager');
    }

    /**
     * To get AdminSecurity\ResetPasswordManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function adminResetPassword(): AdminSecurity\ResetPasswordManager
    {
        return $this->adminResetPasswordLocator->get('security_admin_reset_password_form_manager');
    }

    /**
     * To get AdminSecurity\RoleManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function adminRole(): AdminSecurity\RoleManager
    {
        return $this->adminRoleLocator->get('security_admin_role_form_manager');
    }

    /**
     * To get AdminSecurity\RoleManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function adminUser(): AdminSecurity\UserManager
    {
        return $this->adminUserLocator->get('security_admin_user_form_manager');
    }

    /**
     * To get FrontSecurity\ConfirmPasswordManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function frontConfirmPassword(): FrontSecurity\ConfirmPasswordManager
    {
        return $this->frontConfirmPasswordLocator->get('security_front_confirm_password_form_manager');
    }

    /**
     * To get FrontSecurity\ProfileManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function frontProfile(): FrontSecurity\ProfileManager
    {
        return $this->frontProfileLocator->get('security_front_profile_form_manager');
    }

    /**
     * To get FrontSecurity\RegisterManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function frontRegister(): FrontSecurity\RegisterManager
    {
        return $this->frontRegisterLocator->get('security_front_register_form_manager');
    }

    /**
     * To get FrontSecurity\ResetPasswordManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function frontResetPassword(): FrontSecurity\ResetPasswordManager
    {
        return $this->frontResetPasswordLocator->get('security_front_reset_password_form_manager');
    }

    /**
     * To get FrontSecurity\UserManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function frontUser(): FrontSecurity\UserManager
    {
        return $this->frontUserLocator->get('security_front_user_form_manager');
    }
}