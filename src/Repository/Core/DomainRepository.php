<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Configuration;
use App\Entity\Core\Domain;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * DomainRepository.
 *
 * @extends ServiceEntityRepository<Domain>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DomainRepository extends ServiceEntityRepository
{
    private ?string $host;

    /**
     * DomainRepository constructor.
     */
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
        $this->host = $this->coreLocator->request() ? $this->coreLocator->request()->getHost() : null;
        parent::__construct($this->registry, Domain::class);
    }

    /**
     * Find by name and locale.
     *
     * @throws NonUniqueResultException
     */
    public function findByNameAndLocale(string $name, string $locale): ?Domain
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.configuration', 'c')
            ->innerJoin('c.website', 'w')
            ->andWhere('d.name = :name')
            ->andWhere('d.locale = :locale')
            ->setParameter('name', $name)
            ->setParameter('locale', $locale)
            ->addSelect('c')
            ->addSelect('w')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by name.
     */
    public function findByName(?string $name = null): ?Domain
    {
        $name = !empty($name) ? $name : $this->host;
        $name = str_replace(['https://', 'http://'], '', $name);

        $result = $this->createQueryBuilder('d')
            ->andWhere('d.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getResult();

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Find by name.
     */
    public function findDefaultByConfigurationAndLocaleArray(Configuration $configuration, string $locale): ?array
    {
        $result = $this->createQueryBuilder('d')
            ->andWhere('d.configuration = :configuration')
            ->andWhere('d.locale = :locale')
            ->andWhere('d.asDefault = :asDefault')
            ->setParameter('configuration', $configuration)
            ->setParameter('locale', $locale)
            ->setParameter('asDefault', true)
            ->getQuery()
            ->getArrayResult();

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Find by ConfigurationModel.
     */
    public function findByConfiguration(Configuration $configuration): ?array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.configuration = :configuration')
            ->setParameter('configuration', $configuration)
            ->getQuery()
            ->getResult();
    }
}
