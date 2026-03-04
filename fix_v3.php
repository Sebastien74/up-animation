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

    // Étape 1 : Extraire row_attr de attr s'il est imbriqué (cas d'erreur CustomIntlType.php)
    // Pattern: 'attr' => [ ... 'row_attr' => [ ... ], ... ]
    $content = preg_replace_callback(
        "/'attr'\s*=>\s*\[((?:[^\[\]]+|\[(?:[^\[\]]+|\[[^\[\]]*\])*\])*)\]/s",
        function ($matches) {
            $attrInner = $matches[1];
            
            // On cherche row_attr à l'intérieur
            if (preg_match("/'row_attr'\s*=>\s*(\[(?:[^\[\]]+|\[[^\[\]]*\])*\])/s", $attrInner, $rowMatch)) {
                $rowAttrFull = "'row_attr' => " . $rowMatch[1];
                
                // Supprimer row_attr de attrInner
                $newAttrInner = preg_replace("/'row_attr'\s*=>\s*\[(?:[^\[\]]+|\[[^\[\]]*\])*\],?\s*/s", "", $attrInner);
                $newAttrInner = trim($newAttrInner, " \t\n\r,");

                if (empty($newAttrInner)) {
                    return $rowAttrFull;
                } else {
                    // On force le retour à la ligne pour row_attr
                    return "'attr' => [" . $newAttrInner . "],\n                " . $rowAttrFull;
                }
            }
            return $matches[0];
        },
        $content
    );

    // Étape 2 : S'assurer que 'attr' => [...] et 'row_attr' => [...] sont sur des lignes séparées
    // S'ils sont sur la même ligne : 'attr' => [...], 'row_attr' => [...]
    $content = preg_replace_callback(
        "/'attr'\s*=>\s*(\[(?:[^\[\]]+|\[[^\[\]]*\])*\])\s*,\s*'row_attr'\s*=>/s",
        function ($matches) {
            // On vérifie s'il y a déjà un retour à la ligne
            if (!str_contains($matches[0], "\n")) {
                return "'attr' => " . $matches[1] . ",\n                'row_attr' =>";
            }
            return $matches[0];
        },
        $content
    );

    // Étape 3 : S'assurer du responsive (col-12 devant les col-md/lg/etc)
    // Et s'assurer que si on a un row_attr, il a bien col-12 s'il a une classe de grille
    $content = preg_replace_callback(
        "/'row_attr'\s*=>\s*\[\s*'class'\s*=>\s*['\"]([^'\"]+)['\"]\s*\]/s",
        function ($matches) {
            $classValue = $matches[1];
            if (preg_match('/col-(md|lg|sm|xl)-\d+/', $classValue) && !str_contains($classValue, 'col-12')) {
                $classValue = "col-12 " . $classValue;
            }
            return "'row_attr' => ['class' => '" . $classValue . "']";
        },
        $content
    );

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Fixed: $filePath\n";
    }
}
