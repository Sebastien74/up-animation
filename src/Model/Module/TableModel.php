<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Entity\Module\Table\Table;
use App\Model\BaseModel;
use App\Model\EntityModel;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;

/**
 * TableModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class TableModel extends BaseModel
{
    /**
     * fromEntity.
     *
     * @throws MappingException|NonUniqueResultException|QueryException
     */
    public static function fromEntity(Table $table, CoreLocatorInterface $coreLocator): object
    {
        $model = (array) EntityModel::fromEntity($table, $coreLocator)->response;

        return (object) array_merge($model, [
            'render' => self::render($table)
        ]);
    }

    /**
     * Order Table entity for view.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private static function render(Table $table): array
    {
        $results = [];
        $results['haveColsTitles'] = false;

        foreach ($table->getCols() as $col) {
            $colModel = ViewModel::fromEntity($col, self::$coreLocator);
            $results['head'][$col->getPosition()] = [
                'entity' => $col,
                'intl' => $colModel->intl,
            ];
            ksort($results['head']);
            if ($colModel->intl->title) {
                $results['haveColsTitles'] = true;
            }
            foreach ($col->getCells() as $cell) {
                $results['body'][$cell->getPosition()][] = [
                    'cell' => $cell,
                    'intl' => ViewModel::fromEntity($cell, self::$coreLocator)->intl,
                    'col' => $col,
                ];
                ksort($results['body']);
            }
        }

        foreach ($results as $result) {
            if (is_array($result)) {
                ksort($result);
            }
        }

        return $results;
    }
}
