<?php

declare(strict_types=1);

namespace App\Model\Core;

use App\Entity\Core\Configuration;
use App\Entity\Core\Transition;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * DomainModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class TransitionModel extends BaseModel
{
    /**
     * DomainModel constructor.
     */
    public function __construct(
        public readonly ?array $list = null,
    ) {
    }

    /**
     * Get model.
     */
    public static function fromEntity(Configuration $configuration, CoreLocatorInterface $coreLocator): self
    {
        self::setLocator($coreLocator);

        return new self(
            list: self::transitions($configuration),
        );
    }

    /**
     * To get transitions.
     */
    private static function transitions(Configuration $configuration): array
    {
        $filesystem = new Filesystem();
        $dirname = self::$coreLocator->cacheDir().'/transitions.cache.json';

        if (!$filesystem->exists($dirname)) {
            $reflector = new \ReflectionClass(Transition::class);
            $properties = array_merge(
                $reflector->getProperties(\ReflectionProperty::IS_PUBLIC),
                $reflector->getProperties(\ReflectionProperty::IS_PROTECTED),
                $reflector->getProperties(\ReflectionProperty::IS_PRIVATE),
            );

            $transitions = [];
            foreach ($configuration->getTransitions() as $transition) {
                if ($transition->isActiveForBlock() || $transition->isActive()) {
                    $slug = $transition->getSection() ?: $transition->getSlug();
                    if ($slug) {
                        $data = [];
                        foreach ($properties as $property) {
                            if (!$property->isStatic()) {
                                $value = self::getContent($property->getName(), $transition);
                                if (!is_object($value)) {
                                    $data[$property->getName()] = $value;
                                }
                            }
                        }
                        $transitions[$configuration->getId()][$slug] = $data;
                    }
                }
            }

            $fp = fopen($dirname, 'w');
            fwrite($fp, json_encode($transitions, JSON_PRETTY_PRINT));
            fclose($fp);
        }

        $json = (array) json_decode(file_get_contents($dirname));

        return isset($json[$configuration->getId()]) ? (array) $json[$configuration->getId()] : [];
    }
}
