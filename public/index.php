<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

defined('UNDER_MAINTENANCE') || define('UNDER_MAINTENANCE', false);
defined('ALLOWED_WEBMASTER') || define('ALLOWED_WEBMASTER', false);

if (UNDER_MAINTENANCE) {
    $isWebmaster = ALLOWED_WEBMASTER && in_array($_SERVER['REMOTE_ADDR'] ?? '', require dirname(__DIR__).'/config/trusted_ips.php', true);
    if (!$isWebmaster) {
        require dirname(__DIR__).'/config/maintenance.php';
        exit;
    }
}

try {
    require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
} catch (\Throwable $platformException) {
    if (str_contains($platformException->getMessage(), 'platform')) {
        require dirname(__DIR__).'/config/platform_error.php';
    } else {
        require dirname(__DIR__).'/config/boot_error.php';
    }
    exit;
}

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
