<?php

declare(strict_types=1);

namespace App\Service\Pdf;

use App\Entity\Information\Address;
use App\Entity\Information\Email;
use App\Entity\Information\Phone;
use App\Service\Interface\CoreLocatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * ProductPdfRenderer.
 *
 * Render a catalog product (or a collection) into a binary PDF string using Dompdf.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ProductPdfRenderer
{
    private const string TEMPLATE_SINGLE = 'front/default/actions/catalog/print/product.html.twig';
    private const string TEMPLATE_MANY = 'front/default/actions/catalog/print/favorites.html.twig';
    private const int GALLERY_LIMIT = 8;
    private const bool DISPLAY_ADDRESS = true;

    public function __construct(
        private readonly \Twig\Environment $templating,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
    }

    /**
     * Render a single product as binary PDF.
     */
    public function render(object $product, array $context = []): string
    {
        $publicDir = $this->publicDir();
        $projectDir = dirname($publicDir);
        $website = $context['website'] ?? null;
        $shared = $this->sharedContext($publicDir, $projectDir, $website);
        $page = $this->buildPageContext($publicDir, $product);

        $html = $this->templating->render(self::TEMPLATE_SINGLE, array_merge([
            'entity' => $product,
            'mainImage' => $page['mainImage'],
            'gallery' => $page['gallery'],
            'logoPath' => $shared['logoPath'],
            'brand' => $shared['brand'],
            'fonts' => $shared['fonts'],
        ], $context));

        return $this->htmlToPdf($html, $projectDir);
    }

    /**
     * Render a collection of products as a single binary PDF (one product per page break).
     *
     * @param array<object>      $products
     * @param array<string,mixed> $context Accepts 'website' and 'productUrls' as array<int, string>
     */
    public function renderMany(array $products, array $context = []): string
    {
        $publicDir = $this->publicDir();
        $projectDir = dirname($publicDir);
        $website = $context['website'] ?? null;
        $productUrls = is_array($context['productUrls'] ?? null) ? $context['productUrls'] : [];
        $shared = $this->sharedContext($publicDir, $projectDir, $website);

        $pages = [];
        foreach ($products as $product) {
            $page = $this->buildPageContext($publicDir, $product);
            $productId = property_exists($product, 'id') ? (int) $product->id : 0;
            $pages[] = [
                'entity' => $product,
                'mainImage' => $page['mainImage'],
                'gallery' => $page['gallery'],
                'productUrl' => $productUrls[$productId] ?? null,
            ];
        }

        $html = $this->templating->render(self::TEMPLATE_MANY, [
            'pages' => $pages,
            'logoPath' => $shared['logoPath'],
            'brand' => $shared['brand'],
            'fonts' => $shared['fonts'],
        ]);

        return $this->htmlToPdf($html, $projectDir);
    }

    /**
     * Public directory without trailing separator.
     */
    private function publicDir(): string
    {
        return rtrim($this->coreLocator->publicDir(), DIRECTORY_SEPARATOR.'/');
    }

    /**
     * Resolve per-product image data (main + gallery).
     *
     * @return array{mainImage: ?string, gallery: array<int,string>}
     */
    private function buildPageContext(string $publicDir, object $product): array
    {
        $mainImage = $this->resolveImage($publicDir, property_exists($product, 'mainMedia') ? $product->mainMedia : null);

        $gallery = [];
        $others = property_exists($product, 'mediasWithoutMain') && is_iterable($product->mediasWithoutMain)
            ? $product->mediasWithoutMain : [];
        foreach ($others as $media) {
            $resolved = $this->resolveImage($publicDir, $media);
            if (null !== $resolved) {
                $gallery[] = $resolved;
                if (count($gallery) >= self::GALLERY_LIMIT) {
                    break;
                }
            }
        }

        return [
            'mainImage' => $mainImage,
            'gallery' => $gallery,
        ];
    }

    /**
     * Build the brand/logo/fonts context shared across pages.
     *
     * @return array{logoPath: ?string, brand: array<string,mixed>, fonts: array<string,string>}
     */
    private function sharedContext(string $publicDir, string $projectDir, mixed $website): array
    {
        $fontsDir = $projectDir.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'Poppins'.DIRECTORY_SEPARATOR;

        return [
            'logoPath' => $this->resolveLogo($publicDir, $website),
            'brand' => $this->resolveBrand($website),
            'fonts' => [
                'regular' => $fontsDir.'Poppins-Regular.ttf',
                'medium' => $fontsDir.'Poppins-Medium.ttf',
                'semibold' => $fontsDir.'Poppins-SemiBold.ttf',
                'bold' => $fontsDir.'Poppins-Bold.ttf',
            ],
        ];
    }

    /**
     * Build and execute Dompdf rendering from HTML.
     */
    private function htmlToPdf(string $html, string $projectDir): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', $projectDir);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Resolve a media (object, string path, or array) into an absolute filesystem path if it exists.
     */
    private function resolveImage(string $publicDir, mixed $media): ?string
    {
        $path = null;
        $type = null;

        if (is_string($media)) {
            $path = $media;
        } elseif (is_object($media)) {
            $path = property_exists($media, 'path') ? $media->path : null;
            $type = property_exists($media, 'type') ? $media->type : null;
        } elseif (is_array($media)) {
            $path = $media['path'] ?? ($media['dirname'] ?? null);
        }

        if (!is_string($path) || '' === $path) {
            return null;
        }
        if (null !== $type && 'img' !== $type) {
            return null;
        }

        $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $absolute = $publicDir.DIRECTORY_SEPARATOR.$relative;

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * Resolve the brand logo from the website model, trying common slots.
     */
    private function resolveLogo(string $publicDir, mixed $website): ?string
    {
        if (!is_object($website)) {
            return null;
        }

        $logos = property_exists($website, 'logos') && is_array($website->logos) ? $website->logos : [];
        $candidates = ['logo', 'footer', 'email'];
        foreach ($candidates as $key) {
            if (!isset($logos[$key])) {
                continue;
            }
            $resolved = $this->resolveImage($publicDir, $logos[$key]);
            if (null !== $resolved) {
                return $resolved;
            }
        }

        $direct = property_exists($website, 'logo') ? $website->logo : null;
        if (null !== $direct) {
            return $this->resolveImage($publicDir, $direct);
        }

        return null;
    }

    /**
     * Build a flat, template-ready brand block from the website model.
     *
     * @return array{name: string, address: ?string, zipCode: ?string, city: ?string, country: ?string, phone: ?string, phoneHref: ?string, email: ?string, baseline: ?string}
     */
    private function resolveBrand(mixed $website): array
    {
        $name = 'Up Animations!';
        $address = $zipCode = $city = $country = $phone = $email = null;

        if (is_object($website)) {

            if (property_exists($website, 'companyName') && is_string($website->companyName) && '' !== $website->companyName) {
                $name = $website->companyName;
            }

            $information = property_exists($website, 'information') ? $website->information : null;
            $addr = is_object($information) && property_exists($information, 'address') ? $information->address : null;
            if ($addr instanceof Address) {
                $address = self::DISPLAY_ADDRESS ? $addr->getAddress() : null;
                $zipCode = self::DISPLAY_ADDRESS ? $addr->getZipCode() : null;
                $city = self::DISPLAY_ADDRESS ? $addr->getCity() : null;
                $country = self::DISPLAY_ADDRESS ? $addr->getCountry() : null;
                foreach ($addr->getPhones() as $phoneEntity) {
                    if ($phoneEntity instanceof Phone && $phoneEntity->getNumber()) {
                        $phone = $phoneEntity->getNumber();
                        break;
                    }
                }
                foreach ($addr->getEmails() as $emailEntity) {
                    if ($emailEntity instanceof Email && $emailEntity->getEmail()) {
                        $email = $emailEntity->getEmail();
                        break;
                    }
                }
            }

            if (null === $phone && property_exists($website, 'phones') && is_array($website->phones)) {
                foreach ($website->phones as $phoneEntity) {
                    if ($phoneEntity instanceof Phone && $phoneEntity->getNumber()) {
                        $phone = $phoneEntity->getNumber();
                        break;
                    }
                }
            }
            if (null === $email && property_exists($website, 'emails') && is_array($website->emails)) {
                foreach ($website->emails as $emailEntity) {
                    if ($emailEntity instanceof Email && $emailEntity->getEmail()) {
                        $email = $emailEntity->getEmail();
                        break;
                    }
                }
            }
        }

        return [
            'name' => $name,
            'address' => $address,
            'zipCode' => $zipCode,
            'city' => $city,
            'country' => $country,
            'phone' => $phone,
            'phoneHref' => $phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $phone) : null,
            'email' => $email,
            'baseline' => 'Animations événementielles',
        ];
    }
}
