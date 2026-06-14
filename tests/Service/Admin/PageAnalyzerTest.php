<?php

declare(strict_types=1);

namespace App\Tests\Service\Admin;

use App\Service\Admin\PageAnalyzer;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Behavioural tests for the page analyzer: detection of findings, own-host
 * exclusion and calibrated score. The analyzer is pure (parses an HTML string),
 * so no container is needed; the translator is an identity stub.
 */
final class PageAnalyzerTest extends TestCase
{
    private function analyzer(): PageAnalyzer
    {
        $translator = new class implements TranslatorInterface {
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $id;
            }
        };

        return new PageAnalyzer($translator);
    }

    /**
     * @param array<string, mixed> $report
     *
     * @return array<string, mixed>|null
     */
    private static function finding(array $report, string $id): ?array
    {
        foreach ($report['groups'] as $group) {
            foreach ($group['findings'] as $finding) {
                if ($finding['id'] === $id) {
                    return $finding;
                }
            }
        }

        return null;
    }

    public function testEmptyHtmlReturnsErrorReport(): void
    {
        $report = $this->analyzer()->analyze('   ');

        self::assertNull($report['score']);
        self::assertSame(1, $report['summary']['high']);
        self::assertNotNull(self::finding($report, 'empty'));
    }

    public function testDetectsImageWithoutDimensions(): void
    {
        $report = $this->analyzer()->analyze('<html><body><img src="/a.jpg"></body></html>');

        $finding = self::finding($report, 'img-dimensions');
        self::assertNotNull($finding);
        self::assertSame('high', $finding['severity']);
        self::assertSame(1, $report['meta']['images']);
    }

    public function testExcludesOwnHostFromExternalDomains(): void
    {
        $html = <<<'HTML'
            <html><head><link rel="canonical" href="https://example.com/page"></head>
            <body>
                <a href="https://example.com/autre">interne absolu</a>
                <script src="https://cdn.tiers.com/app.js"></script>
                <img src="https://images.tiers.com/x.jpg">
            </body></html>
            HTML;

        $report = $this->analyzer()->analyze($html, 'page');

        // example.com (own host) excluded; only the two third-party hosts counted.
        self::assertSame(2, $report['meta']['externalDomains']);
    }

    public function testExplicitOwnHostOverridesCanonical(): void
    {
        $html = '<html><body><img src="https://cdn.example.com/x.jpg"><a href="https://example.com/a">a</a></body></html>';

        $report = $this->analyzer()->analyze($html, 'page', 'example.com');

        // example.com excluded via explicit own host; cdn.example.com still counts.
        self::assertSame(1, $report['meta']['externalDomains']);
    }

    public function testScoreIsCalibratedAndBounded(): void
    {
        $good = $this->analyzer()->analyze($this->goodHtml());
        $bad = $this->analyzer()->analyze($this->badHtml());

        self::assertGreaterThanOrEqual(0, $bad['score']);
        self::assertLessThanOrEqual(100, $good['score']);
        self::assertGreaterThanOrEqual(75, $good['score'], 'A clean page should score high.');
        self::assertGreaterThan($bad['score'], $good['score'], 'A clean page must score higher than a poor one.');
    }

    private function goodHtml(): string
    {
        return <<<'HTML'
            <!doctype html><html lang="fr"><head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Une page de test correcte et lisible</title>
            <meta name="description" content="Une description suffisamment longue pour dépasser le seuil minimal de cinquante caractères sans difficulté.">
            <link rel="canonical" href="https://example.com/page">
            <link rel="icon" href="/favicon.ico">
            <link rel="preload" as="font" href="/f.woff2" crossorigin>
            </head><body>
            <main><h1>Titre principal</h1>
            <img src="/img/a.webp" width="800" height="600" loading="lazy" alt="visuel" srcset="/img/a.webp 800w">
            </main></body></html>
            HTML;
    }

    private function badHtml(): string
    {
        return <<<'HTML'
            <html><head>
            <script src="/blocking.js"></script>
            </head><body onclick="go()">
            <img src="/a.jpg">
            <img src="/b.png">
            <a href="#"></a>
            </body></html>
            HTML;
    }
}
