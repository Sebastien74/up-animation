<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Seo;

/**
 * SeoFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface SeoFormManagerInterface
{
    public function importRedirection(): Seo\ImportRedirectionManager;
    public function redirection(): Seo\RedirectionManager;
    public function url(): Seo\UrlManager;
}