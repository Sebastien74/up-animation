<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Model\Core\WebsiteModel;
use App\Service\Content;
use App\Service\Core;
use App\Service\Core\InterfaceHelper;
use App\Service\Doctrine\QueryServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CoreLocatorInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface CoreLocatorInterface
{
    public function website(): ?WebsiteModel;

    public function seoService(): Content\SeoService;

    public function treeService(): Core\TreeService;

    public function listingService(): Content\ListingService;

    public function thumbService(): Content\ThumbService;

    public function interfaceHelper(): InterfaceHelper;

    public function cacheService(): Core\CacheServiceInterface;

    public function requestStack(): HttpFoundation\RequestStack;

    public function request(): ?HttpFoundation\Request;

    public function currentRequest(): ?HttpFoundation\Request;

    public function schemeAndHttpHost(): ?string;

    public function locale(): ?string;

    public function inAdmin(): bool;

    public function translator(): TranslatorInterface;

    public function entityManager(): EntityManagerInterface;

    public function em(): EntityManagerInterface;

    public function router(): RouterInterface;

    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array;

    public function jsonLog(string $text, string $type = 'critical', string $filename = 'critical');

    public function tokenStorage(): TokenStorageInterface;

    public function user(): ?UserInterface;

    public function authorizationChecker(): AuthorizationCheckerInterface;

    public function lastRoute(): Core\LastRouteService;

    public function redirectionService(): Content\RedirectionService;

    public function fileInfo(): Core\FileInfo;

    public function emQuery(): QueryServiceInterface;

    public function ai(): Core\AI;

    public function XssProtectionData(mixed $value = null): string|array|null;

    public function metadata(mixed $entity, string $fieldName): object|bool;

    public function markdown(?string $string = null): Content\MarkdownServiceInterface;

    public function checkIP(?WebsiteModel $website = null): bool;

    public function fileExist(?string $path = null, string $dir = '/templates/'): bool;

    public function routeExist(string $routeName): bool;

    public function checkRoute(string $routeName): bool;

    public function alphanumericKey(int $length = 15): ?string;

    public function unescape(?string $string = null): ?string;

    public function preloadFiles(): array;

    public function projectDir(): string;

    public function publicDir(): string;

    public function uploadDir(): string;

    public function cacheDir(): string;

    public function logDir(): string;

    public function isDebug(): bool;

    public function envName(): string;
}
