<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Form\Manager as FormManager;
use App\Service\Admin as AdminService;

/**
 * AdminLocatorInterface.
 *
 * To load admin Services
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface AdminLocatorInterface
{
    public function formHelper(): AdminService\FormHelper;

    public function treeHelper(): AdminService\TreeHelper;

    public function indexHelper(): AdminService\IndexHelper;

    public function formDuplicateHelper(): AdminService\FormDuplicateHelper;

    public function clearMediasService(): AdminService\ClearMediasService;

    public function searchFilterService(): AdminService\SearchFilterService;

    public function videoService(): AdminService\VideoService;

    public function positionService(): AdminService\PositionService;

    public function deleteService(): AdminService\DeleteService;

    public function titleService(): AdminService\TitleService;

    public function globalManager(): FormManager\Core\GlobalManager;

    public function urlManager(): FormManager\Seo\UrlManager;

    public function layoutManager(): FormManager\Layout\LayoutManager;

    public function treeManager(): FormManager\Core\TreeManager;

    public function intlManager(): FormManager\Translation\IntlManager;

    public function deleteManagers(): DeleteInterface;

    public function importManagers(): ImportInterface;

    public function exportManagers(): ExportInterface;

    public function tooHeavyFiles(mixed $entity): array;

    public function mediasAlert(array $entity): array;

    public function routeArgs(string $route): array;
}
