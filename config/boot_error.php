<?php

/**
 * Standalone, dependency-free error page rendered when the application fails to
 * boot for any non-platform reason, before Symfony can take over.
 *
 * It is included from public/index.php and must not rely on the autoloader,
 * Twig or the Webpack build. Expects a $platformException (\Throwable) in scope.
 */

declare(strict_types=1);

/** @var \Throwable $platformException */
$message = isset($platformException) ? $platformException->getMessage() : '';

// Best-effort debug detection: Dotenv has not booted yet, so only real env vars are visible.
$isDebug = filter_var($_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL);
// Trust only the real TCP peer; X-Forwarded-For is client-spoofable.
$isTrustedIp = in_array($_SERVER['REMOTE_ADDR'] ?? '', require __DIR__ . '/trusted_ips.php', true);
$showDetail = $isDebug || $isTrustedIp;

$pageTitle = 'Erreur de démarrage';
$eyebrow = 'Diagnostic · Démarrage';
$headingHtml = "L'application n'a pas pu <em>démarrer</em>.";
$lede = "Une erreur est survenue avant le chargement de l'application. "
    . 'Le service est momentanément indisponible.';
$panelLabel = 'boot sequence';
$panelRows = [[
    'label' => 'Séquence de démarrage',
    'ok' => false,
    'value' => 'Interrompue',
    'target' => null,
]];
$hintHtml = "Réessayez dans un instant. Si le problème persiste, contactez l'administrateur du site.";
$detail = $message;
$statusCode = 500;

require __DIR__ . '/error_page.php';
