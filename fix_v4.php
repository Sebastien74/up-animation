<?php

$dir = __DIR__ . '/src/Form';

$it = new RecursiveDirectoryIterator($dir);
foreach (new RecursiveIteratorIterator($it) as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Étape 1 : Remplacer 'group' => 'col-...' par 'row_attr' => ['class' => 'col-12 col-...']
    // Cas 1 : 'group' est dans 'attr'
    $content = preg_replace_callback(
        "/'attr'\s*=>\s*\[([^\]]*'group'\s*=>\s*(['\"](col-[^'\"]+)['\"]|\\$[^,\]\s]+)[^\]]*)\]/s",
        function ($matches) use ($filePath) {
            $attrInner = $matches[1];
            $groupValue = $matches[2];
            $groupLiteral = $matches[3] ?? null;

            // Retirer group de attrInner
            $newAttrInner = preg_replace("/'group'\s*=>\s*[^,\]]+,?\s*/s", "", $attrInner);
            $newAttrInner = trim($newAttrInner, " \t\n\r,");

            // Calculer la nouvelle classe
            $newClass = $groupValue;
            if ($groupLiteral) {
                if (preg_match('/^col-(md|lg|sm|xl)-(\d+)$/', $groupLiteral)) {
                    $newClass = "'col-12 " . $groupLiteral . "'";
                }
            }

            $res = "";
            if (empty($newAttrInner)) {
                $res = "'row_attr' => ['class' => " . $newClass . "]";
            } else {
                $res = "'attr' => [" . $newAttrInner . "],\n                'row_attr' => ['class' => " . $newClass . "]";
            }
            
            if (empty($res)) {
                return $matches[0];
            }
            return $res;
        },
        $content
    );

    // Cas 2 : 'group' est isolé dans les options
    $content = preg_replace_callback(
        "/(?<=,|\s|\[)'group'\s*=>\s*(['\"](col-[^'\"]+)['\"]|\\$[^,\]\s]+)/s",
        function ($matches) {
            $groupValue = $matches[1];
            $groupLiteral = $matches[2] ?? null;

            $newClass = $groupValue;
            if ($groupLiteral) {
                if (preg_match('/^col-(md|lg|sm|xl)-(\d+)$/', $groupLiteral)) {
                    $newClass = "'col-12 " . $groupLiteral . "'";
                }
            }
            return "'row_attr' => ['class' => " . $newClass . "]";
        },
        $content
    );

    // Étape 2 : S'assurer du responsive sur les 'row_attr' existants
    $content = preg_replace_callback(
        "/'row_attr'\s*=>\s*\[\s*'class'\s*=>\s*(['\"](col-[^'\"]+)['\"])/s",
        function ($matches) {
            $fullValue = $matches[1];
            $classLiteral = $matches[2];
            
            if (preg_match('/col-(md|lg|sm|xl)-\d+/', $classLiteral) && !str_contains($classLiteral, 'col-12')) {
                return "'row_attr' => ['class' => 'col-12 " . $classLiteral . "'";
            }
            return $matches[0];
        },
        $content
    );

    // Étape 3 : S'assurer que 'attr' et 'row_attr' sont sur des lignes séparées
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

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Updated: $filePath\n";
    }
}
