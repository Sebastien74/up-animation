<?php

declare(strict_types=1);

namespace App\Repository\Media;

use App\Entity\Core\Website;
use App\Entity\Media\ThumbAction;
use App\Model\Core\ConfigurationModel;
use App\Model\Core\WebsiteModel;
use App\Repository\Core\ConfigurationRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ThumbActionRepository.
 *
 * @extends ServiceEntityRepository<ThumbAction>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ThumbActionRepository extends ServiceEntityRepository
{
    private array $cache = [];

    /**
     * ThumbActionRepository constructor.
     */
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly ConfigurationRepository $configurationRepository,
    ) {
        parent::__construct($this->registry, ThumbAction::class);
    }

    /**
     * Find by WebsiteModel.
     */
    public function findByWebsite(WebsiteModel $website): array
    {
        if (!empty($this->cache['byWebsite'][$website->id])) {
            return $this->cache['byWebsite'][$website->id];
        }

        $configuration = $website->configuration;

        if (!$configuration instanceof ConfigurationModel) {
            $configurationId = $this->configurationRepository->findOneBy(['website' => $website->entity])->getId();
        } else {
            $configurationId = $configuration->id;
        }

        $result = $this->createQueryBuilder('t')
            ->leftJoin('t.configuration', 'c')
            ->leftJoin('t.blockType', 'bt')
            ->leftJoin('c.configuration', 'cc')
            ->andWhere('c.configuration = :configuration')
            ->setParameter('configuration', $configurationId)
            ->addSelect('c')
            ->addSelect('cc')
            ->addSelect('bt')
            ->getQuery()
            ->getResult();

        if ($result) {
            $this->cache['byWebsite'][$website->id] = $result;
        }

        return $result;
    }

    /**
     * Find for entity.
     */
    public function findForEntity(mixed $website, string $namespace, ?string $action = null, mixed $filterId = null, ?string $filterBlock = null): array
    {
        $configurationId = $website instanceof Website ? $website->getConfiguration()->getId() : $website['configuration']['id'];

        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.configuration', 'c')
            ->leftJoin('c.configuration', 'cc')
            ->andWhere('t.namespace = :namespace')
            ->andWhere('c.configuration = :configuration')
            ->setParameter('namespace', $namespace)
            ->setParameter('configuration', $configurationId)
            ->addSelect('c')
            ->addSelect('cc');

        if ($action) {
            $qb->andWhere('t.action = :action')
                ->setParameter('action', $action);
        }

        if ($filterId) {
            $qb->andWhere('t.actionFilter = :actionFilter')
                ->setParameter('actionFilter', $filterId);
        }

        if ($filterBlock) {
            $qb->leftJoin('t.blockType', 'bt')
                ->andWhere('bt.slug = :slug')
                ->setParameter('slug', $filterBlock)
                ->addSelect('bt');
        }

        $result = $qb->getQuery()
            ->getArrayResult();

        if (!$filterId) {
            foreach ($result as $thumb) {
                if (!$thumb['actionFilter']) {
                    return $thumb;
                }
            }
        }

        return $result ? $result[0] : [];
    }

    /**
     * Get thumb by namespace and filter.
     */
    public function findByNamespaceAndFilter(WebsiteModel $website, string $namespace, mixed $actionFilter): ?ThumbAction
    {
        $result = $this->createQueryBuilder('t')
            ->leftJoin('t.configuration', 'c')
            ->leftJoin('c.configuration', 'cc')
            ->andWhere('t.namespace = :namespace')
            ->andWhere('t.actionFilter = :actionFilter')
            ->andWhere('c.configuration = :configuration')
            ->setParameter('namespace', $namespace)
            ->setParameter('actionFilter', $actionFilter)
            ->setParameter('configuration', $website->configuration->entity)
            ->addSelect('c')
            ->addSelect('cc')
            ->getQuery()
            ->getResult();

        return $result ? $result[0] : null;
    }
}
