<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Model\MediaModel;

/**
 * ImageThumbnailInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface ImageThumbnailInterface
{
    public function execute(?MediaModel $mediaModel = null, array $thumbs = [], array $options = [], bool $generator = false): mixed;

    public function getThumbnail(object $thumbInfos, array $runtimeConfig, ?string $filter, array $options = []): string;

    public function getSizes(): array;

    public function getRetinaSizes(): array;

    public function getMaxFileSize(): int;

    public function getMaxFileWidth(): int;

    public function getAllowedExtensions(): array;

    public function getExceptionsExtensions(): array;
}
