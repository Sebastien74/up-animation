<?php

$dir = __DIR__ . '/src/Form';

$it = new RecursiveDirectoryIterator($dir);
foreach (new RecursiveIteratorIterator($it) as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    if (empty($content)) continue;
    
    $originalContent = $content;

    // 1. Déplacer group de attr vers row_attr
    // Pattern plus robuste pour capturer 'attr' => [ ... 'group' => '...' ]
    $content = preg_replace_callback(
        "/'attr'\s*=>\s*\[([^\]]*'group'\s*=>\s*['\"](col-[^'\"]+)['\"][^\]]*)\]/s",
        function ($matches) {
            $attrInner = $matches[1];
            $groupValue = $matches[2];
            
            $newAttrInner = preg_replace("/'group'\s*=>\s*['\"]col-[^'\"]+['\"],?\s*/s", "", $attrInner);
            $newAttrInner = trim($newAttrInner, " \t\n\r,");

            $newClass = $groupValue;
            if (preg_match('/^col-(md|lg|sm|xl)-(\d+)$/', $groupValue)) {
                $newClass = "col-12 " . $groupValue;
            }

            if (empty($newAttrInner)) {
                return "'row_attr' => ['class' => '" . $newClass . "']";
            } else {
                return "'attr' => [" . $newAttrInner . "],\n                'row_attr' => ['class' => '" . $newClass . "']";
            }
        },
        $content
    );

    // 2. Transformer group direct en row_attr
    $content = preg_replace_callback(
        "/(?<=,|\s|\[)'group'\s*=>\s*['\"](col-[^'\"]+)['\"]/s",
        function ($matches) {
            $groupValue = $matches[1];
            $newClass = $groupValue;
            if (preg_match('/^col-(md|lg|sm|xl)-(\d+)$/', $groupValue)) {
                $newClass = "col-12 " . $groupValue;
            }
            return "'row_attr' => ['class' => '" . $newClass . "']";
        },
        $content
    );

    // 3. S'assurer que row_attr est sur une nouvelle ligne après attr (si pas déjà fait)
    $content = preg_replace_callback(
        "/'attr'\s*=>\s*(\[(?:[^\[\]]+|\[[^\[\]]*\])*\])\s*,\s*'row_attr'\s*=>/s",
        function ($matches) {
            if (!str_contains($matches[0], "\n")) {
                return "'attr' => " . $matches[1] . ",\n                'row_attr' =>";
            }
            return $matches[0];
        },
        $content
    );

    // 4. Ajouter col-12 aux row_attr existants s'il manque
    $content = preg_replace_callback(
        "/'row_attr'\s*=>\s*\[\s*'class'\s*=>\s*['\"]([^'\"]*col-(md|lg|sm|xl)-\d+[^'\"]*)['\"]\s*\]/s",
        function ($matches) {
            $classValue = $matches[1];
            if (!str_contains($classValue, 'col-12')) {
                 $classValue = "col-12 " . $classValue;
            }
            return "'row_attr' => ['class' => '" . $classValue . "']";
        },
        $content
    );

    if ($content !== $originalContent && !empty($content)) {
        file_put_contents($filePath, $content);
        echo "Updated: $filePath\n";
    }
}
