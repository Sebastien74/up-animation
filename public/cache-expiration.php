<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require dirname(__DIR__) . '/config/bootstrap.php';

$pathMatches = explode('?', $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI']);
$path = $pathMatches[0];
$filesystem = new Filesystem();

if($filesystem->exists($path)) {

    $expirations = [
        'video/mp4' => '31536000',
        'video/ogg' => '31536000',
        'video/ogv' => '31536000',
        'video/webm' => '31536000',
    ];

    $file = new File($path);
    $file = new UploadedFile($file->getPathname(), $file->getFilename(), $file->getMimeType(), NULL, true);
    $extension = $file->getExtension();
    $mimeType = $file->getMimeType();

    /** Default expiration one year */
    $expiration = !empty($expirations[$mimeType]) ? $expirations[$mimeType] : '31536000';

    header('Content-type: ' . $mimeType);
    header('Cache-Control: max-age=' . $expiration);


//    $size = filesize($path);
//    $begin = 0;
//    $end = $size;
//
//    if (isset($_SERVER['HTTP_RANGE'])) {
//        if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
//            $begin = intval($matches[0]);
//            if (!empty($matches[1])) {
//                $end = intval($matches[1]);
//            }
//        }
//    }
//
//    header('Content-type: ' . $mimeType);
//    header('Accept-Ranges: bytes');
//    header('Content-Length:' . $size);
//    header("Content-Disposition: inline;");
//    header("Content-Range: bytes $begin-$end/$size");
//    header("Content-Transfer-Encoding: binary\n");
//    header('Connection: close');
//    header('Cache-Control: max-age=' . $expiration);

    if (is_file($path)) {
        readfile($path);
    }
}