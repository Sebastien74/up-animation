<?php

declare(strict_types=1);

namespace App\Service\Axonaut;

/**
 * AxonautClientInterface.
 *
 * Thin wrapper around the Axonaut CRM REST API (https://axonaut.com/api/v2/doc).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface AxonautClientInterface
{
    /**
     * Whether the integration is usable (enabled + API key set).
     */
    public function isAvailable(): bool;

    /**
     * Create a company (prospect). Returns the Axonaut company id or null on failure.
     */
    public function createCompany(string $name, string $comments = ''): ?int;

    /**
     * Create an employee (contact) attached to a company. Returns the id or null.
     */
    public function createEmployee(int $companyId, ?string $firstname, ?string $lastname, ?string $email, ?string $phone): ?int;

    /**
     * Create an opportunity attached to a company. Returns the id or null.
     */
    public function createOpportunity(int $companyId, string $name, string $comments = ''): ?int;
}
