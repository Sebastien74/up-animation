<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core as CoreEntities;
use App\Entity\Gdpr as GdprEntities;
use App\Entity\Media as MediaEntities;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Yaml\Yaml;

/**
 * GdprFixtures.
 *
 * Gdpr Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => GdprFixtures::class, 'key' => 'gdpr_fixtures'],
])]
class GdprFixtures
{
    private MediaEntities\Folder $mediaFolder;
    private CoreEntities\Website $website;
    private CoreEntities\Configuration $configuration;
    private ?User $user;

    /**
     * GdprFixtures constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UploadedFileFixtures $uploader,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Add GDPR entities.
     */
    public function add(MediaEntities\Folder $webmasterFolder, CoreEntities\Website $website, ?User $user = null): void
    {
        $this->website = $website;
        $this->configuration = $website->getConfiguration();
        $this->mediaFolder = $this->uploader->generateFolder($website, 'RGPD', 'gdpr', $webmasterFolder, $user);
        $this->user = $user;
        foreach ($this->getCategoriesParams() as $key => $categoryParams) {
            $category = $this->generateCategory($categoryParams, $key + 1);
            $this->generateGroup($category);
        }
        $this->entityManager->flush();
    }

    /**
     * Get Category[] params.
     */
    private function getCategoriesParams(): array
    {
        return [
            ['slug' => 'functional', 'name' => 'Cookies de fonctionnement'],
            ['slug' => 'display', 'name' => "Cookies d'affichage"],
            ['slug' => 'audience', 'name' => 'Cookies de mesure d’audience'],
            ['slug' => 'social', 'name' => 'Cookies des réseaux sociaux'],
            ['slug' => 'marketing', 'name' => 'Cookies marketing et autres cookies'],
        ];
    }

    /**
     * Generate Category.
     */
    private function generateCategory(array $categoryParams, int $position): GdprEntities\Category
    {
        $params = (object) $categoryParams;
        $category = new GdprEntities\Category();
        $category->setAdminName($params->name);
        $category->setSlug($params->slug);
        $category->setPosition($position);
        $category->setConfiguration($this->configuration);
        $category->setCreatedBy($this->user);
        $this->entityManager->persist($category);

        return $category;
    }

    /**
     * Get Group[] params.
     */
    private function getGroupsParams(string $slug): array
    {
        $path = $this->projectDir.'/bin/data/fixtures/gdpr-group.yaml';
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $groups = Yaml::parseFile($path);

        return !empty($groups[$slug]) ? $groups[$slug] : [];
    }

    /**
     * Generate Group.
     */
    private function generateGroup(GdprEntities\Category $category): void
    {
        $groups = $this->getGroupsParams($category->getSlug());
        $position = 1;

        foreach ($groups as $name => $groupParams) {
            $groupParams = (object) $groupParams;
            $anonymize = property_exists($groupParams, 'anonymize') ? $groupParams->anonymize : false;

            $group = new GdprEntities\Group();
            $group->setAdminName($name);
            $group->setActive($groupParams->active);
            $group->setAnonymize($anonymize);
            $group->setService($groupParams->service);
            $group->setGdprcategory($category);
            $group->setPosition($position);
            $group->setCreatedBy($this->user);

            $this->entityManager->persist($group);
            $this->entityManager->flush();

            ++$position;

            foreach ($groupParams->intls as $locale => $intlConfig) {
                $intlConfig = (object) $intlConfig;
                $intl = new GdprEntities\GroupIntl();
                $intl->setLocale($locale);
                $intl->setTitle($name);
                $intl->setIntroduction('<p>'.$intlConfig->introduction.'</p>');
                $intl->setBody('<p>'.$intlConfig->body.'</p>');
                $intl->setTargetLink($intlConfig->link);
                $intl->setCreatedBy($this->user);
                $intl->setWebsite($this->website);

                $group->addIntl($intl);

                $this->entityManager->persist($intl);

                $path = $this->projectDir.'/assets/medias/images/gdpr/'.$group->getSlug().'-gdpr.svg';
                $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
                $media = $this->uploader->uploadedFile($this->website, $path, $locale, $group, 'gdpr', 'gdpr', $this->user);

                if ($media instanceof MediaEntities\Media) {
                    $media->setFolder($this->mediaFolder);
                    $this->entityManager->persist($media);
                    $this->entityManager->flush();
                }
            }

            $this->cookies($group);
        }
    }

    /**
     * Generate Cookies.
     */
    private function cookies(GdprEntities\Group $group): void
    {
        $cookies = $this->getCookies($group->getSlug());
        foreach ($cookies as $key => $cookieName) {
            $cookie = new GdprEntities\Cookie();
            $cookie->setAdminName($cookieName);
            $cookie->setPosition($key + 1);
            $cookie->setSlug($cookieName.'-'.$group->getSlug());
            $cookie->setCode($cookieName);
            $cookie->setGdprgroup($group);
            $cookie->setCreatedBy($this->user);
            $this->entityManager->persist($cookie);
        }
    }

    /**
     * Get Cookie[] params.
     */
    private function getCookies(string $slug): array
    {
        $cookies['php'] = ['PHPSESSID', 'REMEMBERME'];
        $cookies['google-analytics'] = ['_ga', '_gat', '_gid'];
        $cookies['google-tag-manager'] = ['_ga', '_gid', '_gcl_au'];
        $cookies['tawk-to'] = ['TawkConnectionTime', '__tawkuuid'];
        $cookies['facebook'] = ['fr'];

        return !empty($cookies[$slug]) ? $cookies[$slug] : [];
    }
}
