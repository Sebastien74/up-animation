<?php

declare(strict_types=1);

namespace App\Form\Manager\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\Redirection;
use App\Service\Core\Urlizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;

/**
 * RedirectionManager.
 *
 * Manage admin Redirection form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => RedirectionManager::class, 'key' => 'seo_redirection_form_manager'],
])]
class RedirectionManager
{
    private bool $asSSL;

    /**
     * RedirectionManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $cacheDir)
    {
        $this->asSSL = 'https' === $_ENV['APP_PROTOCOL'];
    }

    /**
     * @preUpdate
     */
    public function preUpdate(mixed $entity, Website $website, array $interface, Form $form): void
    {
        if ($entity instanceof Website) {
            foreach ($form->get('redirections')->getData() as $redirection) {
                if (!$redirection->getWebsite()) {
                    $redirection->setWebsite($website);
                    $this->entityManager->persist($redirection);
                }
            }
        }
    }

    /**
     * onFlush.
     */
    public function onFlush(mixed $entity, Website $website): void
    {
        $redirections = $this->entityManager->getRepository(Redirection::class)->findBy(['website' => $website]);
        $this->setProtocol($redirections);
        $this->setCache($redirections);
    }

    /**
     * Set Protocol in old URL.
     */
    private function setProtocol(array $redirections): void
    {
        foreach ($redirections as $redirection) {
            if (str_contains($redirection->getOld(), 'http:') && $this->asSSL) {
                $old = str_replace('http:', 'https:', $redirection->getOld());
                $redirection->setOld($old);
                $this->entityManager->persist($redirection);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * To set cache.
     */
    public function setCache(array $redirections): void
    {
        $dirname = $this->clearCache();
        $cacheData = [];
        foreach ($redirections as $redirection) {
            $cacheData['redirection.'.$redirection->getLocale().'.'.$redirection->getWebsite()->getId().'.'.Urlizer::urlize($redirection->getOld())] = $redirection->getNew();
        }
        $cache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
        $cache->warmUp($cacheData);
    }

    /**
     * To clear cache.
     */
    public function clearCache(): string
    {
        $filesystem = new Filesystem();
        $dirname = $this->cacheDir.'/redirections.cache';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        if ($filesystem->exists($dirname)) {
            $filesystem->remove($dirname);
        }

        return $dirname;
    }
}
