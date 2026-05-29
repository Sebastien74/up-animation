<?php

declare(strict_types=1);

namespace App\Service\Development;

/**
 * DocumentationSection.
 *
 * Immutable representation of one documentation page (one markdown file).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class DocumentationSection
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $excerpt,
        public string $markdown,
    ) {
    }
}
