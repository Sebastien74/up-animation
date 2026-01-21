<?php

declare(strict_types=1);

namespace App\Service\Core;

use Doctrine\Persistence\ManagerRegistry;

/**
 * DoctrineService.
 *
 * Manage Doctrine
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DoctrineService
{
    /**
     * DoctrineService constructor.
     */
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    public function directFetchAll(string $sql): mixed
    {
        $entityManager = $this->doctrine->getManager('direct');
        $conn = $entityManager->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAllAssociativeIndexed();
    }
}