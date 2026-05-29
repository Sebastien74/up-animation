<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

try {
    require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
} catch (\Throwable $platformException) {
    // Composer's platform check (e.g. PHP version mismatch) throws before the
    // framework can boot. Render a standalone, dependency-free error page.
    if (str_contains($platformException->getMessage(), 'platform')) {
        require dirname(__DIR__).'/config/platform_error.php';
        exit;
    }
    throw $platformException;
}

/** To set under maintenance status */
const UNDER_MAINTENANCE = false;
const MAINTENANCE_ALLOWED_IPS = [];
if (UNDER_MAINTENANCE) {
    $_ENV['UNDER_MAINTENANCE'] = UNDER_MAINTENANCE;
    $_ENV['MAINTENANCE_ALLOWED_IPS'] = MAINTENANCE_ALLOWED_IPS;
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
