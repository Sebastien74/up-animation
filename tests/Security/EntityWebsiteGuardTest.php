<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Media\Media;
use App\Entity\Module\Catalog\Product;
use App\Entity\Security\Group;
use App\Entity\Security\Role;
use App\Entity\Security\User;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * A1 IDOR guard: an entity may only be acted upon when its website matches the
 * route website (unless the user is ROLE_INTERNAL). Covers Page, Product, Media.
 */
final class EntityWebsiteGuardTest extends KernelTestCase
{
    private const int SITE_A = 1;
    private const int SITE_B = 2;

    private CoreLocatorInterface $coreLocator;
    private TokenStorageInterface $tokenStorage;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->coreLocator = $container->get(CoreLocatorInterface::class);
        $this->tokenStorage = $container->get(TokenStorageInterface::class);

        $token = $_ENV['SECURITY_TOKEN'] ?? 'token';
        $request = Request::create('/admin-'.$token.'/'.self::SITE_A.'/test');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get(RequestStack::class)->push($request);
    }

    public function testScopedUserCannotReachAnotherSiteEntity(): void
    {
        $this->login();
        $this->setRouteWebsite(self::SITE_A);

        self::assertFalse($this->coreLocator->isEntityWebsiteAllowed($this->entity(Page::class, self::SITE_B)));
        self::assertFalse($this->coreLocator->isEntityWebsiteAllowed($this->entity(Product::class, self::SITE_B)));
        self::assertFalse($this->coreLocator->isEntityWebsiteAllowed($this->entity(Media::class, self::SITE_B)));
    }

    public function testScopedUserCanReachSameSiteEntity(): void
    {
        $this->login();
        $this->setRouteWebsite(self::SITE_A);

        self::assertTrue($this->coreLocator->isEntityWebsiteAllowed($this->entity(Page::class, self::SITE_A)));
        self::assertTrue($this->coreLocator->isEntityWebsiteAllowed($this->entity(Product::class, self::SITE_A)));
        self::assertTrue($this->coreLocator->isEntityWebsiteAllowed($this->entity(Media::class, self::SITE_A)));
    }

    public function testMultiSiteUserIsScopedToTheCurrentRoute(): void
    {
        // Same user manages both sites; on route B they edit B's entities, not A's.
        $this->login();
        $this->setRouteWebsite(self::SITE_B);

        self::assertTrue($this->coreLocator->isEntityWebsiteAllowed($this->entity(Page::class, self::SITE_B)));
        self::assertFalse($this->coreLocator->isEntityWebsiteAllowed($this->entity(Page::class, self::SITE_A)));
    }

    public function testInternalUserBypassesGuard(): void
    {
        $this->login(['ROLE_INTERNAL']);
        $this->setRouteWebsite(self::SITE_A);

        self::assertTrue($this->coreLocator->isEntityWebsiteAllowed($this->entity(Page::class, self::SITE_B)));
        self::assertTrue($this->coreLocator->isEntityWebsiteAllowed($this->entity(Product::class, self::SITE_B)));
    }

    public function testFailOpenWhenWebsiteUndeterminable(): void
    {
        $this->login();
        $this->setRouteWebsite(self::SITE_A);

        self::assertTrue($this->coreLocator->isEntityWebsiteAllowed(new Product()));
    }

    private function login(array $roles = []): void
    {
        $user = new User();
        if ($roles) {
            $group = new Group();
            foreach ($roles as $name) {
                $group->addRole((new Role())->setName($name));
            }
            $user->setGroup($group);
        }
        $this->tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    }

    private function setRouteWebsite(int $id): void
    {
        $reflection = new \ReflectionObject($this->coreLocator);
        $property = $reflection->getProperty('cache');
        $cache = $property->getValue($this->coreLocator);
        $cache['adminWebsite'] = new WebsiteModel(id: $id);
        $property->setValue($this->coreLocator, $cache);
    }

    private function entity(string $class, int $websiteId): object
    {
        $website = new Website();
        (new \ReflectionProperty(Website::class, 'id'))->setValue($website, $websiteId);

        $entity = new $class();
        $entity->setWebsite($website);

        return $entity;
    }
}
