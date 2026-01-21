<?php

declare(strict_types=1);

namespace App\Service\Development;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * CopyBundleService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ClearBundleService implements ClearBundleInterface
{
    /**
     * ClearBundleService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * To execute service.
     */
    public function execute(): void
    {
        $bundlesDirname = $this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'sfcms';
        $finder = new Finder();
        $finder->directories()->in($bundlesDirname)->depth(0);
        foreach ($finder->directories() as $directory) {
            $composerFinder = new Finder();
            $composerFinder->files()->in($directory->getRealPath())->name('composer.json')->contains('app:copy:bundle')->depth(0);
            if (1 === $composerFinder->count()) {
                $composerFile = null;
                foreach ($composerFinder->files() as $file) {
                    $composerFile = $file;
                    break;
                }
                if ($composerFile) {
                    $file = new File($composerFile->getRealPath());
                    $jsonDecoder = new JsonDecode();
                    $data = $jsonDecoder->decode($file->getContent(), 'json');
                    $this->removePackage($data->name, $directory);
                }
            }
        }
    }

    /**
     * To remove package.
     */
    private function removePackage(string $name, \SplFileInfo $directory): void
    {
        $this->clearComposer($name, 'lock');
        $this->clearComposer($name, 'json');
        $this->clearInstalled($name, 'json');
        $this->clearInstalled($name, 'php');
        $filesystem = new Filesystem();
        if ($filesystem->exists($directory)) {
            $filesystem->remove($directory);
        }
    }

    /**
     * To clear composer.
     */
    private function clearComposer(string $bundleName, string $extension): void
    {
        if ($bundleName) {
            $composerDirname = $this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'composer.'.$extension;
            $file = new File($composerDirname);
            $serializer = new Serializer([new GetSetMethodNormalizer()], ['json' => new JsonEncoder()]);
            $jsonDecoder = new JsonDecode();
            $data = $jsonDecoder->decode($file->getContent(), 'json', ['json_decode_associative' => true]);
            if (is_array($data) && !empty($data['require'][$bundleName])) {
                unset($data['require'][$bundleName]);
                $jsonContent = $serializer->serialize($data, 'json', [
                    'json_encode_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ]);
                file_put_contents($composerDirname, $jsonContent);
            } elseif (is_array($data) && !empty($data['packages']) && is_array($data['packages'])) {
                $packages = [];
                foreach ($data['packages'] as $package) {
                    if (is_array($package) && !empty($package['name']) && $bundleName !== $package['name']) {
                        $packages[] = $package;
                    }
                }
                $data['packages'] = $packages;
                $jsonContent = $serializer->serialize($data, 'json', [
                    'json_encode_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ]);
                file_put_contents($composerDirname, $jsonContent);
            }
        }
    }

    /**
     * To clear installed.
     */
    private function clearInstalled(string $bundleName, string $extension): void
    {
        if ($bundleName) {
            $installedDirname = $this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'composer'.DIRECTORY_SEPARATOR.'installed.'.$extension;
            $filesystem = new Filesystem();
            $file = $filesystem->exists($installedDirname) ? new File($installedDirname) : null;
            if ($file && 'json' === $extension) {
                $serializer = new Serializer([new GetSetMethodNormalizer()], ['json' => new JsonEncoder()]);
                $jsonDecoder = new JsonDecode();
                $data = $jsonDecoder->decode($file->getContent(), 'json', ['json_decode_associative' => true]);
                $packages = [];
                foreach ($data['packages'] as $package) {
                    if (is_array($package) && !empty($package['name']) && $bundleName !== $package['name']) {
                        $packages[] = $package;
                    }
                }
                $data['packages'] = $packages;
                $jsonContent = $serializer->serialize($data, 'json', [
                    'json_encode_options' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ]);
                file_put_contents($installedDirname, $jsonContent);
            } elseif ($file && 'php' === $extension) {
                $data = include $installedDirname;
                if (isset($data['versions'][$bundleName])) {
                    unset($data['versions'][$bundleName]);
                    $content = var_export($data, true);
                    $pattern = $this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'composer';
                    $pattern = str_replace(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, $pattern);
                    $content = str_replace($pattern, '__DIR__', $content);
                    $content = preg_replace("/'__DIR__\/\.\.\/(.*?)'/", '__DIR__ . \'/../$1\'', $content);
                    $content = '<?php return '.$content.';';
                    file_put_contents($installedDirname, $content);
                }
            }
        }
    }
}
