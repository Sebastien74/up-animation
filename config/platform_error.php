<?php

/**
 * Standalone, dependency-free error page rendered when Composer's platform
 * check fails (e.g. PHP version mismatch) before Symfony can boot.
 *
 * It is included from public/index.php and must not rely on the autoloader,
 * Twig or the Webpack build. Expects a $platformException (\Throwable) in scope.
 */

declare(strict_types=1);

/** @var \Throwable $platformException */
$message = isset($platformException) ? $platformException->getMessage() : '';

$current = PHP_VERSION;
$required = null;
if (preg_match('/version\s+">=?\s*([0-9.]+)"/', $message, $m)) {
    $required = $m[1];
}
$needs64bit = str_contains($message, '64-bit');

$panelRows = [];
$panelRows[] = [
    'label' => 'Version PHP',
    'ok' => $required === null || version_compare($current, $required, '>='),
    'value' => $current,
    'target' => $required !== null ? '>= ' . $required : null,
];
if ($needs64bit) {
    $panelRows[] = [
        'label' => 'Architecture',
        'ok' => PHP_INT_SIZE === 8,
        'value' => (PHP_INT_SIZE * 8) . '-bit',
        'target' => '64-bit',
    ];
}

$hintRequired = htmlspecialchars($required ?? '8.5', ENT_QUOTES, 'UTF-8');

$pageTitle = 'Environnement incompatible';
$eyebrow = 'Diagnostic · Environnement';
$headingHtml = "L'environnement d'exécution<br>n'est pas <em>compatible</em>.";
$lede = "L'application n'a pas pu démarrer : la plateforme PHP courante ne satisfait pas "
    . 'les prérequis déclarés par le projet.';
$panelLabel = 'platform check';
$hintHtml = 'Sous WampServer, bascule la version de PHP du virtual host vers '
    . "<code>&gt;= {$hintRequired}</code> (icône Wamp &rarr; PHP &rarr; Version), puis recharge la page.";
$detail = $message;
$showDetail = true;
$statusCode = 500;

require __DIR__ . '/error_page.php';
