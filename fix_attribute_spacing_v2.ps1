$files = Get-ChildItem -Path templates -Filter *.html.twig -Recurse

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $originalContent = $content

    # 1. Retirer l'espace ajouté devant les tags Twig au début d'un attribut
    # Ex: class=" {{ ... }}" => class="{{ ... }}"
    $content = [System.Text.RegularExpressions.Regex]::Replace($content, '([a-z-]+)=" (\{[|%])', '$1="$2')

    # 2. Retirer l'espace ajouté devant les tags Twig à l'intérieur d'une valeur d'attribut
    # Ex: class="btn {{ ... }}" => class="btn{{ ... }}" (en fait on veut garder l'espace si c'était class="btn {{")
    # Mais le script précédent a ajouté un espace systématique.
    # L'utilisateur se plaint de: <i class="icm-network-wired "></i>
    # Ça arrive si on a class="icm-network-wired {%- if ... -%} active {%- endif -%}" qui devient class="icm-network-wired  active "
    
    # On va transformer les {%- et -%} en {% et %} à l'intérieur des attributs pour laisser Twig gérer l'espace normalement.
    $attrRegex = '([a-z-]+)="([^"]*?)"'
    $content = [System.Text.RegularExpressions.Regex]::Replace($content, $attrRegex, {
        param($m)
        $attrName = $m.Groups[1].Value
        $attrValue = $m.Groups[2].Value
        
        if ($attrValue -match '\{%-|-%\}') {
            # On remplace {%- par {% et -%} par %}
            $newValue = $attrValue.Replace('{%-', '{%').Replace('-%}', '%}')
            # On nettoie aussi les doubles espaces qui auraient pu être introduits
            $newValue = $newValue.Replace('  ', ' ')
            return "$attrName=""$newValue"""
        }
        return $m.Value
    })
    
    # On fait pareil pour les tags {{ ... }} qui auraient pu avoir un espace ajouté devant par erreur
    # Le script précédent cherchait <[a-z0-9]+{ et mettait un espace.
    # Donc <i class="abc{{ => <i class="abc {{
    # On veut revenir à <i class="abc{{ si c'est collé dans le code source original, 
    # mais c'est difficile de savoir ce qui était original.
    # Cependant, class="abc {{ ... }}" est généralement plus sûr que class="abc{{ ... }}" SAUF si {{ est au début.
    
    # Correction spécifique pour les espaces en fin de classe ou début d'id
    $content = [System.Text.RegularExpressions.Regex]::Replace($content, 'class=" (.*)"', 'class="$1"')
    $content = [System.Text.RegularExpressions.Regex]::Replace($content, 'id=" (.*)"', 'id="$1"')

    if ($content -ne $originalContent) {
        [System.IO.File]::WriteAllText($file.FullName, $content, (New-Object System.Text.UTF8Encoding($false)))
        Write-Output "Fixed: $($file.FullName)"
    }
}
