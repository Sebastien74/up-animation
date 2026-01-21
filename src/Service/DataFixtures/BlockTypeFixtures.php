<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Layout\BlockType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * BlockTypeFixtures.
 *
 * BlockType Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => BlockTypeFixtures::class, 'key' => 'block_type_fixtures'],
])]
class BlockTypeFixtures
{
    private const array DISABLED = [
        'action',
        'alert',
        'blockquote',
        'card',
        'collapse',
        'icon',
        'modal',
        'share',
        'counter',
    ];

    private array $blockTypes;

    /**
     * BlockTypeFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->blockTypes = $this->entityManager->getRepository(BlockType::class)->findAll();
    }

    /**
     * Add BlockType[].
     */
    public function add(Configuration $configuration, bool $devMode, ?Website $websiteToDuplicate = null): void
    {
        if ($websiteToDuplicate instanceof Website) {
            foreach ($websiteToDuplicate->getConfiguration()->getBlockTypes() as $blockType) {
                $configuration->addBlockType($blockType);
            }
        } else {
            foreach ($this->blockTypes as $blockType) {
                if ($devMode || !in_array($blockType->getSlug(), self::DISABLED)) {
                    $configuration->addBlockType($blockType);
                }
            }
            $this->entityManager->persist($configuration);
        }
    }
}
