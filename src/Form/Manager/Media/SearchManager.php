<?php

declare(strict_types=1);

namespace App\Form\Manager\Media;

use App\Entity\Core\Website;
use App\Entity\Media\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * SearchManager.
 *
 * Manage admin search Media[] form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SearchManager::class, 'key' => 'media_search_form_manager'],
])]
class SearchManager
{
    private bool $isInternalUser;

    /**
     * SearchManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $user = !empty($tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    /**
     * To execute service.
     */
    public function search(FormInterface $form, Website $website): array
    {
        $search = method_exists($form, 'getData') && !empty($form->getData()['searchMedia'])
            ? $form->getData()['searchMedia']
            : null;
        $queryResults = $this->getQueryResults($website, $search);

        return $this->parse($queryResults);
    }

    /**
     * Get query result.
     */
    private function getQueryResults(Website $website, ?string $search = null): array
    {
        $result = [];
        $repository = $this->entityManager->getRepository(Media::class);
        $statement = $repository->createQueryBuilder('m');
        $statement->select('m')
            ->andWhere('m.website = :website')
            ->andWhere('m.screen IN (:screens)')
            ->setParameter('website', $website)
            ->setParameter('screens', ['desktop', 'mp4', 'webm', 'vtt']);

        if ($search) {
            try {
                $result = $statement->andWhere('m.filename LIKE :filename')
                    ->orWhere('m.name LIKE :name')
                    ->setParameter('filename', '%'.rtrim($search, '-').'%')
                    ->setParameter('name', '%'.rtrim($search, '-').'%')
                    ->orderBy('m.id', 'DESC')
                    ->getQuery()
                    ->getResult();
            } catch (\Exception $exception) {
                //                dd($exception);
            }
        } else {
            $result = $statement
                ->andWhere('m.folder IS NULL')
                ->andWhere('m.filename IS NOT NULL')
                ->orderBy('m.id', 'DESC')
                ->getQuery()
                ->getResult();
        }

        return $result;
    }

    /**
     * Parse results and remove Internal media for not allowed User.
     */
    private function parse(array $queryResults): array
    {
        $result = [];
        foreach ($queryResults as $media) {
            $media = is_array($media) && !empty($media[0]) ? $media[0] : $media;
            if ($this->isInternalUser || $media instanceof Media && !$media->getFolder() || $media instanceof Media && !$media->getFolder()->isWebmaster()) {
                $result[] = $media;
            }
        }

        return $result;
    }
}
