<?php

$dir = new RecursiveDirectoryIterator('src/Form');
$iterator = new RecursiveIteratorIterator($dir);
$regex = new RegexIterator($iterator, '/\.php$/');

$errors = [];

foreach ($regex as $file) {
    $path = $file->getPathname();
    $output = [];
    $return_var = 0;
    exec("php -l \"$path\" 2>&1", $output, $return_var);
    if ($return_var !== 0) {
        $errors[$path] = implode("\n", $output);
    }
}

if (empty($errors)) {
    echo "No syntax errors found.";
} else {
    foreach ($errors as $path => $error) {
        echo "Error in $path:\n$error\n\n";
    }
}
