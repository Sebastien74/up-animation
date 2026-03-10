<?php

use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

session_start();

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$request = new Request();
$filesystem = new Filesystem();

/**
 * Generate a small error image.
 */
if (!function_exists('generateImage')) {
    #[NoReturn] function generateImage(string $message, string $extension = 'jpeg'): void
    {
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $img = imagecreatetruecolor(180, 45);
            $color = imagecolorallocate($img, 200, 50, 50);
            imagestring($img, 4, 15, 15, $message, $color);
            header('Content-Type: image/jpeg');
            imagejpeg($img);
        } else {
            $mimeType = match ($extension) {
                'css' => 'text/css',
                'js' => 'application/javascript',
                default => 'text/plain',
            };
            header('Content-Type: ' . $mimeType, true, 404);
            echo $message;
        }
        exit;
    }
}

// ----------------------------
// CONFIG
// ----------------------------

$secureToken = $_ENV['SECURITY_TOKEN'] ?? null;
$appSecret = $_ENV['APP_SECRET'] ?? null;

// ----------------------------
// FILE RESOLUTION
// ----------------------------

$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = $_SERVER['DOCUMENT_ROOT'] . $uriPath;
if (!str_contains($filePath, 'public')) {
    $filePath = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['DOCUMENT_ROOT'] . '/public', $filePath);
}

$file = new File($filePath, false);
$extension = strtolower($file->getExtension());

// ----------------------------
// SECURITY CHECKS
// ----------------------------

$validToken  = false;
if (!empty($_COOKIE['SECURITY_TOKEN']) && $_COOKIE['SECURITY_TOKEN'] === $secureToken) {
    $validToken = true;
} elseif (!empty($_SESSION['SECURITY_TOKEN']) && $_SESSION['SECURITY_TOKEN'] === $secureToken) {
    $validToken = true;
}

// Allow build assets if they exist and are JS or CSS, as they are part of the frontend/admin interface
// and may be requested dynamically by Webpack chunks without specific security tokens in the URL.
$isAsset = in_array($extension, ['js', 'css', 'json', 'map']);
if ($isAsset && $filesystem->exists($filePath)) {
    $validToken = true;
}

if (!$validToken) {
    generateImage('Not found', $extension);
}

// ----------------------------
// MIME TYPE DETECTION
// ----------------------------

$mimeType = $file->getMimeType() ?: 'application/octet-stream';

$mimeType = match ($extension) {
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    default => $file->getMimeType() ?: 'application/octet-stream',
};

header('Content-Type: ' . $mimeType);
header('Cache-Control: private, max-age=31536000, immutable');
ini_set('zlib.output_compression', 'Off');
while (ob_get_level() > 0) ob_end_clean();

// ----------------------------
// RESPONSE LOGIC
// ----------------------------

readfile($filePath);
exit;