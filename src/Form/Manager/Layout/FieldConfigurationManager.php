<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * FieldConfigurationManager.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => FieldConfigurationManager::class, 'key' => 'layout_field_configuration_form_manager'],
])]
class FieldConfigurationManager
{
    /**
     * FieldConfigurationManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @onFlush.
     */
    public function onFlush(Block $block, Website $website, array $interface, Form $form): void
    {
        $this->setPositions($block);
    }

    /**
     * To set positions.
     */
    public function setPositions(Block $block): void
    {
        $configuration = $block->getFieldConfiguration();

        $values = [];
        foreach ($configuration->getFieldValues() as $value) {
            $value = $value->getValue() ?: $value;
            if (!$value->getValue()) {
                $values[$value->getPosition()]['entity'] = $value;
                foreach ($value->getValues() as $child) {
                    $values[$value->getPosition()]['children'][$child->getPosition()] = $child;
                }
            }
        }
        ksort($values);

        $position = 1;
        foreach ($values as $value) {
            $valueDb = $value['entity'];
            $valueDb->setPosition($position);
            $this->coreLocator->em()->persist($valueDb);
            ++$position;
            $children = !empty($value['children']) ? $value['children'] : [];
            foreach ($children as $child) {
                $child->setPosition($position);
                $this->coreLocator->em()->persist($child);
                ++$position;
            }
        }

        $this->coreLocator->em()->flush();
    }
}
