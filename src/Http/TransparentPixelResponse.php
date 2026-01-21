<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\Response;

/**
 * TransparentPixelResponse.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TransparentPixelResponse extends Response
{
    /**
     * Base 64 encoded contents for 1px transparent gif and png.
     *
     * @var string
     */
    private const string IMAGE_CONTENT = 'R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==';

    /**
     * The response content type.
     *
     * @var string
     */
    private const string CONTENT_TYPE = 'image/gif';

    /**
     * TransparentPixelResponse __constructor.
     */
    public function __construct()
    {
        $content = base64_decode(self::IMAGE_CONTENT);
        parent::__construct($content);
        $this->headers->set('Content-Type', self::CONTENT_TYPE);
        $this->setPrivate();
        $this->headers->addCacheControlDirective('no-cache', true);
        $this->headers->addCacheControlDirective('must-revalidate', true);
    }
}
