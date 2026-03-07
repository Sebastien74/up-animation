<?php

$dir = new RecursiveDirectoryIterator('src/Form');
$iterator = new RecursiveIteratorIterator($dir);
$regex = new RegexIterator($iterator, '/\.php$/');

$found = [];

foreach ($regex as $file) {
    $content = file_get_contents($file->getPathname());
    
    // Pattern looking for 'row_attr' inside a trans call
    // trans('...', [], 'row_attr' => ...)
    if (preg_match("/trans\([^)]*['\"]row_attr['\"]\s*=>/s", $content)) {
        $found[] = $file->getPathname();
    }
}

echo implode("\n", $found);
