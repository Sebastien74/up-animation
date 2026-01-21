<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/config/bootstrap.php';

$matches = explode('/', $_SERVER['REQUEST_URI']);
$scriptFilename = __DIR__ . '/js/fosjsrouting/' . end($matches);
$content = file_get_contents(__DIR__ . '/js/fosjsrouting/' . end($matches));
$content = '/**/fos.Router.setData(' . $content . ');';

function caching_headers($file, $timestamp)
{
    $gmt_mtime = gmdate('r', $timestamp);

    header('Content-type: application/javascript');
    header('ETag: "' . md5($timestamp . $file) . '"');

    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime
            || isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == md5($timestamp . $file)) {
            header('HTTP/1.1 304 Not Modified');
            exit();
        }
    }

    header('Last-Modified: ' . $gmt_mtime);
    header('Cache-Control: public');
}

caching_headers($scriptFilename, filemtime($scriptFilename));

$request = new Request();
$response = new Response($content);
$response->setEtag(md5($response->getContent()));
$response->setPublic();

print_r($response->getContent());