<?php

declare(strict_types=1);

namespace App\Service\Development\Zend;

use App\Entity\Core\Website;
use App\Entity\Security\Group;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * ZendImport.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZendImport
{
    /**
     * ZendImport constructor.
     */
    public function __construct(private readonly UserBackImport $userBackImport)
    {
    }

    /**
     * Import Users Back.
     *
     * @throws Exception
     */
    public function usersBack(Website $website, ?Group $group = null, ?SymfonyStyle $io = null): void
    {
        $this->userBackImport->import($website, $group, $io);
    }
}
