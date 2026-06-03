<?php

declare(strict_types=1);

namespace App\Tests\Twig\Content;

use App\Model\Core\ConfigurationModel;
use App\Model\Core\InformationModel;
use App\Model\Core\WebsiteModel;
use App\Model\IntlModel;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Content\ColorRuntime;
use App\Twig\Content\ManifestRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

final class ManifestRuntimeTest extends TestCase
{
    private string $projectDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->projectDir = sys_get_temp_dir().'/manifest-test-'.bin2hex(random_bytes(6));
        $this->filesystem->mkdir($this->projectDir.'/public/uploads/up-animations');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDir);
    }

    public function testManifestExposesInstallableContract(): void
    {
        $data = $this->generate(logos: []);

        self::assertFalse($data['prefer_related_applications'], 'prefer_related_applications must be false to keep the web install prompt.');
        self::assertSame('standalone', $data['display']);
        self::assertSame('/', $data['start_url']);
        self::assertSame('/', $data['scope']);
        self::assertSame('Up Animations', $data['name']);
        self::assertSame('Up Animations', $data['short_name']);
        self::assertSame('#ffffff', $data['theme_color']);
        self::assertSame('#ffffff', $data['background_color']);
        self::assertSame([], $data['icons']);
    }

    public function testManifestBuildsIconsFromExistingLogoFiles(): void
    {
        $relativePath = 'uploads/up-animations/android-chrome-192x192.png';
        $this->filesystem->dumpFile($this->projectDir.'/public/'.$relativePath, 'png-bytes');

        $data = $this->generate(logos: ['android-chrome-192x192' => $relativePath]);

        self::assertCount(1, $data['icons']);
        $icon = $data['icons'][0];
        self::assertSame('https://up-animations.test/uploads/up-animations/android-chrome-192x192.png', $icon['src']);
        self::assertSame('192x192', $icon['sizes']);
        self::assertSame('image/png', $icon['type']);
    }

    public function testMaskIconIsFlaggedMaskable(): void
    {
        $relativePath = 'uploads/up-animations/mask-icon.png';
        $this->filesystem->dumpFile($this->projectDir.'/public/'.$relativePath, 'png-bytes');

        $data = $this->generate(logos: ['mask-icon' => $relativePath]);

        self::assertSame('any maskable', $data['icons'][0]['purpose']);
    }

    /**
     * @param array<string, string> $logos
     *
     * @return array<string, mixed>
     */
    private function generate(array $logos): array
    {
        $website = $this->website($logos);
        $runtime = new ManifestRuntime($this->coreLocator(), $this->colorRuntime());

        $filename = $runtime->manifest($website);
        $written = $this->projectDir.'/public/'.$filename;

        self::assertFileExists($written);

        return json_decode((string) file_get_contents($written), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, string> $logos
     */
    private function website(array $logos): WebsiteModel
    {
        return new WebsiteModel(
            id: 1,
            slug: 'test',
            companyName: 'Up Animations',
            uploadDirname: 'up-animations',
            configuration: new ConfigurationModel(id: 1, logos: $logos),
            information: new InformationModel(id: 1, intl: new IntlModel(title: 'Up Animations')),
        );
    }

    private function coreLocator(): CoreLocatorInterface
    {
        $coreLocator = $this->createMock(CoreLocatorInterface::class);
        $coreLocator->method('request')->willReturn(Request::create('https://up-animations.test/'));
        $coreLocator->method('projectDir')->willReturn($this->projectDir);

        return $coreLocator;
    }

    private function colorRuntime(): ColorRuntime
    {
        $colorRuntime = $this->createMock(ColorRuntime::class);
        $colorRuntime->method('color')->willReturn(null);

        return $colorRuntime;
    }
}
