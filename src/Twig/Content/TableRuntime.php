<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Module\Table\Table;
use App\Model\Module\TableModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * TableRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TableRuntime implements RuntimeExtensionInterface
{
    /**
     * TableRuntime constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {

    }

    /**
     * Order Table entity for view.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function table(Table $table): array
    {
        return TableModel::fromEntity($table, $this->coreLocator)->render;
    }
}
