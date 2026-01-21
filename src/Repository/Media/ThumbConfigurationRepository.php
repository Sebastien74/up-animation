<?php

declare(strict_types=1);

namespace App\Repository\Media;

use App\Entity\Core\Configuration;
use App\Entity\Layout\Block;
use App\Entity\Media\ThumbConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ThumbConfigurationRepository.
 *
 * @extends ServiceEntityRepository<ThumbConfiguration>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ThumbConfigurationRepository extends ServiceEntityRepository
{
    /**
     * ThumbConfigurationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ThumbConfiguration::class);
    }

    /**
     * Get ThumbConfiguration by namespaces[].
     *
     * @return array<ThumbConfiguration>
     */
    public function findByNamespaces(array $namespaces, Configuration $configuration): array
    {
        $thumbConfigurations = [];

        if (!$namespaces) {
            return $thumbConfigurations;
        }

        foreach ($namespaces as $namespace) {
            $statement = $this->createQueryBuilder('t')
                ->leftJoin('t.actions', 'a')
                ->leftJoin('t.configuration', 'c')
                ->andWhere('t.configuration = :configuration')
                ->andWhere('a.namespace = :namespace')
                ->setParameter('configuration', $configuration)
                ->setParameter('namespace', $namespace['classname'])
                ->addSelect('c')
                ->addSelect('a');

            if ($namespace['entity'] instanceof Block) {
                $statement->andWhere('a.blockType = :blockType');
                $statement->setParameter('blockType', $namespace['entity']->getBlockType());
            }

            $result = $statement->orderBy('t.height', 'DESC')
                ->getQuery()
                ->getResult();

            if ($result) {
                foreach ($result as $item) {
                    $thumbConfigurations[] = $item;
                }
            }
        }

        $results = [];
        foreach ($thumbConfigurations as $key => $thumbConfiguration) {
            /** @var ThumbConfiguration $thumbConfiguration */
            if (empty($results[$thumbConfiguration->getId()])) {
                $results[$thumbConfiguration->getId()] = $thumbConfiguration;
            }
        }

        return $results;
    }
}
