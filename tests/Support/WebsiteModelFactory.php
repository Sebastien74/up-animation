<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Model\Core\ConfigurationModel;
use App\Model\Core\InformationModel;
use App\Model\Core\WebsiteModel;

final class WebsiteModelFactory
{
    public static function create(
        string $companyName = 'Up Animations!',
        string $emailFrom = 'no-reply@up-animations.test',
        string $emailNoReply = 'no-reply@up-animations.test',
        string $email = 'webmaster@up-animations.test',
        string $host = 'up-animations.test',
        string $locale = 'fr',
        string $template = 'default',
    ): WebsiteModel {
        $schemeAndHttpHost = 'https://'.$host;

        $information = new InformationModel(
            id: 1,
            companyName: $companyName,
            email: $email,
            emailFrom: $emailFrom,
            emailNoReply: $emailNoReply,
        );

        $configuration = new ConfigurationModel(
            id: 1,
            locale: $locale,
            template: $template,
        );

        $hosts = (object) [
            'host' => $host,
            'schemeAndHttpHost' => $schemeAndHttpHost,
        ];

        return new WebsiteModel(
            id: 1,
            slug: 'test',
            companyName: $companyName,
            configuration: $configuration,
            information: $information,
            hosts: $hosts,
            schemeAndHttpHost: $schemeAndHttpHost,
        );
    }
}
