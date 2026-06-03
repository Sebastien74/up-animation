<?php

declare(strict_types=1);

namespace App\Tests\Service\Security;

use App\Service\Security\CaptchaService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;

final class CaptchaServiceTest extends TestCase
{
    private const string KEY = 'website-secret-key';

    private MockClock $clock;

    protected function setUp(): void
    {
        $this->clock = new MockClock('2026-01-01 12:00:00');
    }

    public function testIssueProducesSignedChallenge(): void
    {
        $challenge = $this->service()->issue(self::KEY);

        self::assertSame('SHA-256', $challenge['algorithm']);
        self::assertSame(500, $challenge['maxnumber']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $challenge['challenge']);
        self::assertStringContainsString('?expires=', $challenge['salt']);
        self::assertNotEmpty($challenge['signature']);
    }

    public function testValidSolutionPasses(): void
    {
        $service = $this->service();
        $payload = $this->solve($service->issue(self::KEY));

        $this->clock->sleep(3);

        self::assertTrue($service->verify(self::KEY, $payload, ''));
    }

    public function testFilledHoneypotFails(): void
    {
        $service = $this->service();
        $payload = $this->solve($service->issue(self::KEY));
        $this->clock->sleep(3);

        self::assertFalse($service->verify(self::KEY, $payload, 'i am a bot'));
    }

    public function testTamperedSignatureFails(): void
    {
        $service = $this->service();
        $challenge = $service->issue(self::KEY);
        $challenge['signature'] = str_repeat('0', 64);
        $payload = $this->solve($challenge);
        $this->clock->sleep(3);

        self::assertFalse($service->verify(self::KEY, $payload, ''));
    }

    public function testWrongSecretKeyFails(): void
    {
        $service = $this->service();
        $payload = $this->solve($service->issue(self::KEY));
        $this->clock->sleep(3);

        self::assertFalse($service->verify('another-website-key', $payload, ''));
    }

    public function testWrongNumberFails(): void
    {
        $service = $this->service();
        $challenge = $service->issue(self::KEY);
        $solved = json_decode(base64_decode($this->solve($challenge), true), true);
        $solved['number'] += 1;
        $payload = base64_encode(json_encode($solved));
        $this->clock->sleep(3);

        self::assertFalse($service->verify(self::KEY, $payload, ''));
    }

    public function testTooFastSubmissionFails(): void
    {
        $service = $this->service();
        $payload = $this->solve($service->issue(self::KEY));

        self::assertFalse($service->verify(self::KEY, $payload, ''));
    }

    public function testExpiredChallengeFails(): void
    {
        $service = $this->service();
        $payload = $this->solve($service->issue(self::KEY));

        $this->clock->sleep(601);

        self::assertFalse($service->verify(self::KEY, $payload, ''));
    }

    public function testReplayIsRejected(): void
    {
        $service = $this->service();
        $payload = $this->solve($service->issue(self::KEY));
        $this->clock->sleep(3);

        self::assertTrue($service->verify(self::KEY, $payload, ''));
        self::assertFalse($service->verify(self::KEY, $payload, ''));
    }

    #[DataProvider('malformedPayloads')]
    public function testMalformedPayloadFails(?string $payload): void
    {
        $this->clock->sleep(3);

        self::assertFalse($this->service()->verify(self::KEY, $payload, ''));
    }

    /**
     * @return iterable<string, array{0: string|null}>
     */
    public static function malformedPayloads(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'not base64 json' => ['not-base64!!'];
        yield 'json missing fields' => [base64_encode('{"algorithm":"SHA-256"}')];
        yield 'wrong algorithm' => [base64_encode(json_encode([
            'algorithm' => 'MD5', 'challenge' => 'x', 'number' => 1, 'salt' => 's', 'signature' => 'y',
        ]))];
    }

    private function service(): CaptchaService
    {
        return new CaptchaService(new ArrayAdapter(), $this->clock, maxNumber: 500);
    }

    /**
     * Brute-force the proof-of-work like the browser solver does, then encode the payload.
     *
     * @param array<string, int|string> $challenge
     */
    private function solve(array $challenge): string
    {
        $number = null;
        for ($i = 0; $i <= $challenge['maxnumber']; ++$i) {
            if (hash('sha256', $challenge['salt'].$i) === $challenge['challenge']) {
                $number = $i;
                break;
            }
        }

        self::assertNotNull($number, 'Proof-of-work must be solvable.');

        return base64_encode(json_encode([
            'algorithm' => $challenge['algorithm'],
            'challenge' => $challenge['challenge'],
            'number' => $number,
            'salt' => $challenge['salt'],
            'signature' => $challenge['signature'],
        ]));
    }
}
