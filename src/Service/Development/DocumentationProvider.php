<?php

declare(strict_types=1);

namespace App\Service\Development;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

/**
 * DocumentationProvider.
 *
 * Reads the back-office technical documentation from markdown files and
 * exposes them as pages. Source of truth: templates/admin/page/documentation/content/*.md.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class DocumentationProvider
{
    private readonly string $contentDir;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->contentDir = $projectDir.'/templates/admin/page/documentation/content';
    }

    /**
     * @return array<DocumentationSection>
     */
    public function pages(): array
    {
        if (!is_dir($this->contentDir)) {
            return [];
        }

        $pages = [];
        $finder = (new Finder())->files()->in($this->contentDir)->name('*.md')->sortByName();
        foreach ($finder as $file) {
            $pages[] = $this->parse($file->getBasename('.md'), $file->getContents());
        }

        return $pages;
    }

    public function page(string $slug): ?DocumentationSection
    {
        foreach ($this->pages() as $page) {
            if ($page->slug === $slug) {
                return $page;
            }
        }

        return null;
    }

    private function parse(string $filename, string $content): DocumentationSection
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $content));
        $title = ucfirst(str_replace('-', ' ', $filename));

        // The first level-1 heading becomes the page title and is dropped from the body.
        foreach ($lines as $index => $line) {
            if (preg_match('/^#\s+(.+)$/', trim($line), $matches)) {
                $title = trim($matches[1]);
                unset($lines[$index]);
                break;
            }
        }

        $markdown = trim(implode("\n", $lines));
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $filename));

        return new DocumentationSection($slug, $title, $this->excerpt($markdown), $markdown);
    }

    /**
     * First meaningful paragraph, stripped of markdown markup, used as a tile summary.
     */
    private function excerpt(string $markdown): string
    {
        foreach (explode("\n", $markdown) as $line) {
            $line = trim($line);
            if ('' === $line || preg_match('/^(#|>|[-*+]\s|\d+\.\s|```|\||!\[)/', $line)) {
                continue;
            }
            $line = preg_replace('/[*_`>#\[\]]+/', '', $line);
            $line = trim((string) preg_replace('/\s+/', ' ', (string) $line));

            return mb_strlen($line) > 160 ? mb_substr($line, 0, 157).'...' : $line;
        }

        return '';
    }
}
