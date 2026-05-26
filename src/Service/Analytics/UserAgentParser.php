<?php

declare(strict_types=1);

namespace App\Service\Analytics;

/**
 * UserAgentParser.
 *
 * Extracts coarse dimensions (device, browser, os) from a User-Agent
 * string so the raw header never has to be persisted nor queued.
 *
 * Deliberately simple: covers >95% of real browser traffic.
 * For finer-grained parsing we would pull in a dedicated library,
 * but that cost is not justified at our analytics volume.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class UserAgentParser
{
    /**
     * @return array{device: string, browser: string, os: string}
     */
    public function parse(string $userAgent): array
    {
        return [
            'device' => $this->detectDevice($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'os' => $this->detectOs($userAgent),
        ];
    }

    private function detectDevice(string $ua): string
    {
        if (1 === preg_match('/(tablet|ipad|playbook|silk)/i', $ua)) {
            return 'tablet';
        }
        if (1 === preg_match('/(mobile|android|iphone|ipod|blackberry|iemobile|opera mini)/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function detectBrowser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg/') => 'edge',
            str_contains($ua, 'OPR/'), str_contains($ua, 'Opera') => 'opera',
            str_contains($ua, 'Firefox') => 'firefox',
            str_contains($ua, 'Chrome') => 'chrome',
            str_contains($ua, 'Safari') => 'safari',
            default => 'other',
        };
    }

    private function detectOs(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'windows',
            str_contains($ua, 'Mac OS X') => 'macos',
            str_contains($ua, 'Android') => 'android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad'), str_contains($ua, 'iPod') => 'ios',
            str_contains($ua, 'Linux') => 'linux',
            default => 'other',
        };
    }
}
