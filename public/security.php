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
    #[NoReturn] function generateImage(string $message): void
    {
        $img = imagecreatetruecolor(180, 45);
        $color = imagecolorallocate($img, 200, 50, 50);
        imagestring($img, 4, 15, 15, $message, $color);
        header('Content-Type: image/jpeg');
        imagejpeg($img);
        imagedestroy($img);
        exit;
    }
}

// ----------------------------
// CONFIG
// ----------------------------

$secureToken = $_ENV['SECURITY_TOKEN'] ?? null;
$appSecret = $_ENV['APP_SECRET'] ?? null;

// ----------------------------
// SECURITY CHECKS
// ----------------------------

$validToken  = false;
if (!empty($_COOKIE['SECURITY_TOKEN']) && $_COOKIE['SECURITY_TOKEN'] === $secureToken) {
    $validToken = true;
} elseif (!empty($_SESSION['SECURITY_TOKEN']) && $_SESSION['SECURITY_TOKEN'] === $secureToken) {
    $validToken = true;
}

// ----------------------------
// FILE RESOLUTION
// ----------------------------

$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = $_SERVER['DOCUMENT_ROOT'] . $uriPath;
if (!str_contains($filePath, 'public')) {
    $filePath = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['DOCUMENT_ROOT'] . '/public', $filePath);
}

if (!$filesystem->exists($filePath) || !$validToken) {
    generateImage('Not found');
}

// ----------------------------
// MIME TYPE DETECTION
// ----------------------------

$file = new File($filePath);
$extension = strtolower($file->getExtension());
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