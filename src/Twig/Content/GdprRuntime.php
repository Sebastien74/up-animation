<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Model\Api\ApiModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * GdprRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GdprRuntime implements RuntimeExtensionInterface
{
    /**
     * GdprRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly Environment $twig,
    ) {
    }

    /**
     * Get Cookies.
     */
    public function cookies(): array
    {
        $cookies = [];
        $allModules = $this->coreLocator->website()->configuration->modules;
        $gdprActive = $allModules['gdpr'] ?? null;
        $cookiesNames = $gdprActive ? ['felixCookies'] : ['axeptio_cookies'];
        foreach ($cookiesNames as $name) {
            $cookiesRequest = $this->coreLocator->request()->cookies->get($name);
            $serializer = new Serializer([new GetSetMethodNormalizer()], ['json' => new JsonEncoder()]);
            if (!empty($cookiesRequest)) {
                $cookiesRequest = $serializer->decode($cookiesRequest, 'json');
                foreach ($cookiesRequest as $slug => $cookie) {
                    if (!empty($cookie['slug'])) {
                        $cookies[$cookie['slug']] = $cookie['status'];
                    } else {
                        $cookies[$slug] = $cookie;
                    }
                }
            }
        }

        return $cookies;
    }

    /**
     * Get Cookie by name.
     */
    public function cookie(string $name): bool|string
    {
        $cookies = $this->cookies();

        return $cookies[$name] ?? false;
    }

    /**
     * Set iframe.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function iframe(?string $iframeCode = null, array $options = []): void
    {
        $filesystem = new Filesystem();
        $arguments = array_merge($this->gdprArguments(), $options);
        $arguments['code'] = $imageCode = !empty($options['code']) ? $options['code'] : 'iframe';
        if ($iframeCode && str_contains($iframeCode, 'google.com/maps') && 'gmaps' !== $arguments['code']) {
            $arguments['code'] = $imageCode = 'gmaps';
        }
        if ($iframeCode && str_contains($iframeCode, 'elfsight') && 'apps-elfsight' !== $arguments['code']) {
            $arguments['code'] = $imageCode = 'elfsight';
        }
        $prototypeArguments = array_merge(['iframeCode' => $iframeCode], $arguments);
        $imgDirname = $this->coreLocator->projectDir().'/assets/medias/images/gdpr/'.$imageCode.'-gdpr.svg';
        $imgDirname = $filesystem->exists($imgDirname) ? $imgDirname
            : $this->coreLocator->projectDir().'/assets/medias/images/gdpr/'.$imageCode.'-gdpr.png';
        if (!$filesystem->exists($imgDirname)) {
            $imageCode = 'iframe';
        }
        $prototypeArguments['image'] = $imgDirname && str_contains($imgDirname, '.svg')
            ? 'build/gdpr/images/'.$imageCode.'-gdpr.svg'
            : 'build/gdpr/images/'.$imageCode.'-gdpr.png';
        $arguments['prototype'] = $this->twig->render('gdpr/services/iframe-prototype.html.twig', $prototypeArguments);
        $arguments['prototype_placeholder'] = $this->twig->render('gdpr/services/iframe-prototype-placeholder.html.twig', $prototypeArguments);
        echo $this->twig->render('gdpr/services/iframe.html.twig', $arguments);
    }

    /**
     * Set addThis.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function addThis(?ApiModel $api = null): void
    {
        if ($api instanceof ApiModel && $api->addThis) {
            $arguments['api'] = $api;
            $arguments['prototype'] = $this->twig->render('gdpr/services/add-this-prototype.html.twig', ['api' => $api]);
            $arguments['prototype_placeholder'] = $this->twig->render('gdpr/services/add-this-prototype-placeholder.html.twig', ['api' => $api]);
            echo $this->twig->render('gdpr/services/add-this.html.twig', $arguments);
        }
    }

    /**
     * Set tawkTo.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function tawkTo(?ApiModel $api = null): void
    {
        if ($api instanceof ApiModel && $api->tawkToId && !$this->cookie('tawk-to')) {
            echo $this->twig->render('gdpr/services/tawk-to.html.twig');
        }
    }

    /**
     * To get base arguments.
     */
    public function gdprArguments(): array
    {
        $website = $this->coreLocator->website();
        $modules = $website->configuration->modules;
        $arguments = [];
        $arguments['axeptioId'] = $website->api->custom->axeptioId;
        $arguments['axeptioExternal'] = $website->api->custom->axeptioExternal;
        $arguments['gdprActive'] = isset($modules['gdpr']) && $modules['gdpr']
            || $arguments['axeptioId'] || $arguments['axeptioExternal'];

        return $arguments;
    }
}
