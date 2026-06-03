<?php

declare(strict_types=1);

namespace App\Tests\Controller\Front\Module;

use App\Controller\Front\Module\CryptController;
use App\Entity\Api\Api;
use App\Entity\Core\Website;
use App\Service\Security\CaptchaService;
use App\Service\Security\WebsiteSecretProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Endpoint-level test for the captcha challenge action.
 *
 * The project test suite runs without a database, so this exercises the
 * controller handler directly (website resolved as a mock with a preset secret,
 * hence no persistence) rather than booting the full HTTP kernel. It asserts the
 * JSON contract and that the issued challenge actually solves and verifies.
 */
final class CaptchaChallengeEndpointTest extends TestCase
{
    private const string SECRET = 'website-secret-key';

    public function testEndpointReturnsSolvableSignedChallenge(): void
    {
        $clock = new MockClock('2026-01-01 12:00:00');
        $captcha = new CaptchaService(new ArrayAdapter(), $clock, maxNumber: 500);

        $response = (new CryptController())->captchaChallenge($captcha, $this->secretProvider(), $this->website());

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $challenge = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('SHA-256', $challenge['algorithm']);
        self::assertSame(500, $challenge['maxnumber']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $challenge['challenge']);
        self::assertNotEmpty($challenge['salt']);
        self::assertNotEmpty($challenge['signature']);

        $payload = $this->solve($challenge);
        $clock->sleep(3);
        self::assertTrue($captcha->verify(self::SECRET, $payload, ''), 'Challenge issued by the endpoint must verify.');
    }

    public function testMissingWebsiteReturnsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new CryptController())->captchaChallenge(
            new CaptchaService(new ArrayAdapter(), new MockClock(), maxNumber: 500),
            $this->secretProvider(),
            null
        );
    }

    private function website(): Website
    {
        $api = $this->createMock(Api::class);
        $api->method('getSecuritySecretKey')->willReturn(self::SECRET);
        $api->method('getSecuritySecretIv')->willReturn('website-secret-iv');

        $website = $this->createMock(Website::class);
        $website->method('getApi')->willReturn($api);

        return $website;
    }

    private function secretProvider(): WebsiteSecretProvider
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        return new WebsiteSecretProvider($entityManager);
    }

    /**
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

        self::assertNotNull($number);

        return base64_encode(json_encode([
            'algorithm' => $challenge['algorithm'],
            'challenge' => $challenge['challenge'],
            'number' => $number,
            'salt' => $challenge['salt'],
            'signature' => $challenge['signature'],
        ]));
    }
}
