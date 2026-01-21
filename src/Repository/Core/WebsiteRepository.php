<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\InvalidArgumentException;

/**
 * WebsiteRepository.
 *
 * @extends ServiceEntityRepository<Website>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteRepository extends ServiceEntityRepository
{
    private ?string $host;
    private array $cache = [];

    /**
     * WebsiteRepository constructor.
     */
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly CoreLocatorInterface $coreLocator
    ) {
        $this->host = $this->coreLocator->request() ? $this->coreLocator->request()->getHost() : null;
        parent::__construct($this->registry, Website::class);
    }

    /**
     * Get WebsiteModel.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public function findObject(int $id): ?WebsiteModel
    {
        $website = $this->defaultJoin($this->createQueryBuilder('w'))
            ->andWhere('w.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $website ? WebsiteModel::fromEntity($website, $this->coreLocator) : null;
    }

    /**
     * Get current WebsiteModel.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public function findCurrent(): ?WebsiteModel
    {
        $website = $this->findOneByHost();
        if (!$website->isEmpty) {
            $website = $this->findDefault();
        }

        return $website;
    }

    /**
     * Get WebsiteModel by Host name.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public function findOneByHost(?string $host = null, bool $forceByHost = false, bool $asObject = false)
    {
        $host = !empty($host) ? $host : $this->host;
        $host = $host ? str_replace(['https://', 'http://'], '', $host) : null;

        if (!$asObject && !empty($this->cache[$host])) {
            return $this->cache[$host];
        } elseif ($asObject && !empty($this->cache['asObject'][$host])) {
            return $this->cache['asObject'][$host];
        }

        $website = $this->createQueryBuilder('w')
            ->innerJoin('w.configuration', 'c')
            ->innerJoin('w.security', 's')
            ->innerJoin('w.seoConfiguration', 'sc')
            ->innerJoin('sc.intls', 'sci')
            ->innerJoin('c.domains', 'd')
            ->leftJoin('c.modules', 'mo')
            ->andWhere('d.name = :host')
            ->setParameter('host', $host)
            ->addSelect('c')
            ->addSelect('s')
            ->addSelect('sc')
            ->addSelect('sci')
            ->addSelect('d')
            ->addSelect('mo')
            ->getQuery()
            ->getOneOrNullResult();

        if ($forceByHost && $website && !$asObject) {
            $this->cache['asObject'][$host] = $website;
            $website = WebsiteModel::fromEntity($website, $this->coreLocator);
            $this->cache[$host] = $website;
            return $website;
        }

        $website = $website ?: $this->findDefault($asObject);
        if ($website instanceof Website) {
            $this->cache['asObject'][$host] = $website;
        }

        if ($asObject) {
            return $website;
        }

        $website = $website instanceof Website ? WebsiteModel::fromEntity($website, $this->coreLocator) : ($website instanceof WebsiteModel ? $website : null);
        if (empty($this->cache[$host]) && $website) {
            $this->cache[$host] = $website;
        }

        return $website;
    }

    /**
     * Get WebsiteModel by ID for admin part.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public function findByIdForAdmin(int $id): ?WebsiteModel
    {
        $website = $this->createQueryBuilder('w')
            ->leftJoin('w.configuration', 'c')
            ->leftJoin('w.seoConfiguration', 'sc')
            ->leftJoin('c.modules', 'm')
            ->leftJoin('c.transDomains', 'td')
            ->leftJoin('c.colors', 'co')
            ->andWhere('w.id = :id')
            ->setParameter('id', $id)
            ->addSelect('c')
            ->addSelect('sc')
            ->addSelect('m')
            ->addSelect('td')
            ->addSelect('co')
            ->getQuery()
            ->getOneOrNullResult();

        return $website ? WebsiteModel::fromEntity($website, $this->coreLocator) : null;
    }

    /**
     * Get default WebsiteModel.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public function findDefault(bool $asObject = false): ?WebsiteModel
    {
        $website = $this->createQueryBuilder('w')
            ->leftJoin('w.configuration', 'c')
            ->andWhere('c.asDefault = :asDefault')
            ->setParameter('asDefault', true)
            ->addSelect('c')
            ->getQuery()
            ->getOneOrNullResult();

        if ($asObject) {
            return $website;
        }

        return $website ? WebsiteModel::fromEntity($website, $this->coreLocator) : null;
    }

    /**
     * Get actives WebsiteModel.
     *
     * @return array<Website>
     */
    public function findActives(): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get for switcher.
     *
     * @return array<Website>
     */
    public function findForSwitcher(): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.configuration', 'c')
            ->andWhere('w.active = :active')
            ->andWhere('c.seoStatus = :seoStatus')
            ->setParameter('active', true)
            ->setParameter('seoStatus', true)
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all Websites.
     *
     * @return array<Website>
     */
    public function findAllWebsites(): array
    {
        return $this->createQueryBuilder('w')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get default Join.
     */
    private function defaultJoin(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder
            ->leftJoin('w.configuration', 'c')
            ->leftJoin('w.api', 'a')
            ->leftJoin('w.information', 'i')
            ->leftJoin('w.security', 's')
            ->leftJoin('w.seoConfiguration', 'sc')
            ->leftJoin('c.domains', 'd')
            ->leftJoin('c.transitions', 'ct')
            ->leftJoin('c.modules', 'cm')
            ->leftJoin('c.pages', 'cp')
            ->addSelect('c')
            ->addSelect('a')
            ->addSelect('i')
            ->addSelect('s')
            ->addSelect('sc')
            ->addSelect('d')
            ->addSelect('ct')
            ->addSelect('cm')
            ->addSelect('cp');
    }
}
