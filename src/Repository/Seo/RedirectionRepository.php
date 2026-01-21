<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Seo\Redirection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * RedirectionRepository.
 *
 * @extends ServiceEntityRepository<Redirection>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RedirectionRepository extends ServiceEntityRepository
{
    /**
     * RedirectionRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Redirection::class);
    }

    /**
     * Find redirection for front.
     */
    public function findForFront(Request $request): array
    {
        $matches = explode('?', $request->getRequestUri());
        $uri = is_array($matches) && isset($matches[0]) ? $matches[0] : null;
        $domain = str_replace(['http://', 'https://'], '', $request->getSchemeAndHttpHost());
        $currentRequestUri = 'https://'.$domain.$request->getRequestUri();
        $currentRequestSSLUri = 'https://'.$domain.$request->getRequestUri();

        if ($uri && '/' !== $uri) {
            return $this->createQueryBuilder('r')
                ->leftJoin('r.website', 'w')
                ->andWhere('r.old IN (:old)')
                ->setParameter('old', [$request->getUri(), $currentRequestUri, $currentRequestSSLUri])
                ->addSelect('w')
                ->getQuery()
                ->getArrayResult();
        }

        return [];
    }
}
