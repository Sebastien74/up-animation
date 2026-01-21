<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Table;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * TableManager.
 *
 * Manage admin Table form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => TableManager::class, 'key' => 'module_table_form_manager'],
])]
class TableManager
{
    /**
     * TableManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @prePersist
     */
    public function prePersist(Table\Table $table, Website $website): void
    {
        $this->setStart($table, $website);
    }

    private function setStart(Table\Table $table, Website $website): void
    {
        $col = new Table\Col();
        $table->addCol($col);
        $header = new Table\Cell();
        $col->addCell($header);
        $this->coreLocator->em()->persist($table);
        $this->coreLocator->em()->flush();
    }

    /**
     * Add Col in Table.
     */
    public function addCol(Table\Table $table, Website $website): void
    {
        $col = new Table\Col();
        $col->setPosition(count($table->getCols()) + 1);
        $table->addCol($col);

        if ($table->getCols()->isEmpty()) {
            $newCell = new Table\Cell();
            $col->addCell($newCell);
        } else {
            foreach ($table->getCols()[0]->getCells() as $cell) {
                $newCell = new Table\Cell();
                $newCell->setPosition($cell->getPosition());
                $col->addCell($newCell);
            }
        }

        $this->coreLocator->em()->persist($table);
        $this->coreLocator->em()->flush();
    }

    /**
     * Set Col position.
     */
    public function colPosition(Table\Table $table, Table\Col $col, string $type): void
    {
        $colSetPosition = $col->getPosition();
        $newPosition = 'down' === $type ? $colSetPosition - 1 : $colSetPosition + 1;
        $col->setPosition($newPosition);

        $this->coreLocator->em()->persist($col);

        foreach ($table->getCols() as $colDb) {
            if ($colDb->getId() != $col->getId()) {
                if ($colDb->getPosition() == $newPosition && 'down' === $type) {
                    $colDb->setPosition($colDb->getPosition() + 1);
                    $this->coreLocator->em()->persist($colDb);
                } elseif ($colDb->getPosition() == $newPosition && 'up' === $type) {
                    $colDb->setPosition($colDb->getPosition() - 1);
                    $this->coreLocator->em()->persist($colDb);
                }
            }
        }

        $this->coreLocator->em()->flush();
    }

    /**
     * Delete Col[] Table.
     */
    public function deleteCol(Table\Table $table, Table\Col $col): void
    {
        $colDeletedPosition = $col->getPosition();
        $this->coreLocator->em()->remove($col);

        foreach ($table->getCols() as $colDb) {
            if ($colDb->getPosition() > $colDeletedPosition) {
                $colDb->setPosition($colDb->getPosition() - 1);
                $this->coreLocator->em()->persist($colDb);
            }
        }

        $this->coreLocator->em()->flush();
    }

    /**
     * Add Cell[] row in Table.
     */
    public function addRow(Table\Table $table, Website $website): void
    {
        if ($table->getCols()->isEmpty()) {
            $this->setStart($table, $website);
        } else {
            foreach ($table->getCols() as $col) {
                $newCell = new Table\Cell();
                $newCell->setPosition(count($col->getCells()) + 1);
                $col->addCell($newCell);
            }
        }

        $this->coreLocator->em()->persist($table);
        $this->coreLocator->em()->flush();
    }

    /**
     * Set Cell[] row position.
     */
    public function rowPosition(Table\Table $table, int $position, string $type): void
    {
        $newPosition = 'up' === $type ? $position - 1 : $position + 1;

        $cellsToSet = [];
        foreach ($table->getCols() as $colDb) {
            foreach ($colDb->getCells() as $cell) {
                if ($cell->getPosition() === $position) {
                    $cellsToSet[] = $cell->getId();
                }
            }
        }

        foreach ($table->getCols() as $colDb) {
            foreach ($colDb->getCells() as $cell) {
                $cell = $this->coreLocator->em()->getRepository(Table\Cell::class)->find($cell->getId());
                if ($cell->getPosition() === $position && in_array($cell->getId(), $cellsToSet)) {
                    $cell->setPosition($newPosition);
                    $this->coreLocator->em()->persist($cell);
                    $this->coreLocator->em()->flush();
                } elseif ($cell->getPosition() === $newPosition && !in_array($cell->getId(), $cellsToSet)) {
                    $cell->setPosition($position);
                    $this->coreLocator->em()->persist($cell);
                    $this->coreLocator->em()->flush();
                }
            }
        }
    }

    /**
     * Delete Cell[] row.
     */
    public function deleteRow(Table\Table $table, int $position): void
    {
        foreach ($table->getCols() as $colDb) {
            foreach ($colDb->getCells() as $cell) {
                if ($cell->getPosition() === $position) {
                    $this->coreLocator->em()->remove($cell);
                }
            }
        }

        $this->coreLocator->em()->flush();

        foreach ($table->getCols() as $colDb) {
            foreach ($colDb->getCells() as $cell) {
                if ($cell->getPosition() > $position) {
                    $cell->setPosition($cell->getPosition() - 1);
                    $this->coreLocator->em()->persist($cell);
                }
            }
        }

        $this->coreLocator->em()->flush();
    }
}
