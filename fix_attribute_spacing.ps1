$files = Get-ChildItem -Path templates -Filter *.html.twig -Recurse

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $originalContent = $content

    # 1. Retirer l'espace ajouté devant {%- dans les attributs : class=" {%-  => class="{%-
    # On cherche un nom d'attribut suivi de =" {%-
    $content = [System.Text.RegularExpressions.Regex]::Replace($content, '([a-z-]+)=" {%-', '$1="{%-')

    # 2. Retirer l'espace ajouté après -%} dans les attributs : -%} " => -%}"
    # Bien que mon script précédent n'ait pas explicitement ajouté d'espace après, certains cas peuvent exister.
    # On se concentre surtout sur le début de l'attribut comme rapporté par l'utilisateur.

    # 3. Remplacer {%- par {% et -%} par %} uniquement lorsqu'ils sont à l'intérieur d'un attribut
    # On cherche les motifs à l'intérieur des guillemets d'un attribut.
    # C'est complexe en Regex pur pour du HTML/Twig imbriqué, mais on va cibler les cas courants d'attributs.
    # Ex: class="... {%- ... -%} ..." => class="... {% ... %} ..."

    # On utilise une approche plus prudente : si on trouve une balise Twig collée à une valeur d'attribut ou au début.
    # On remplace {%- par {% et -%} par %} si c'est dans un attribut class, id, href, etc.
    
    # On cible spécifiquement les cas problématiques rapportés : id=" main-preloader" et class="... "
    # En fait, l'utilisateur dit "Je pense que tu peux simplement retirer le {%- quand c'est pour un attribut d'élement"
    
    # On va chercher les balises Twig qui sont à l'intérieur de guillemets doubles (typique des attributs HTML)
    # Et on enlève le contrôle d'espacement pour ces balises.
    
    # Regex pour trouver les attributs et modifier les balises Twig à l'intérieur
    $attrRegex = '([a-z-]+)="([^"]*?)"'
    $content = [System.Text.RegularExpressions.Regex]::Replace($content, $attrRegex, {
        param($m)
        $attrName = $m.Groups[1].Value
        $attrValue = $m.Groups[2].Value
        
        # Si la valeur contient des balises Twig avec contrôle d'espacement
        if ($attrValue -match '\{%-|-%\}') {
            # On remplace {%- par {% et -%} par %} dans cette valeur spécifique
            $newValue = $attrValue.Replace('{%-', '{%').Replace('-%}', '%}')
            return "$attrName=""$newValue"""
        }
        return $m.Value
    })

    if ($content -ne $originalContent) {
        [System.IO.File]::WriteAllText($file.FullName, $content, (New-Object System.Text.UTF8Encoding($false)))
        Write-Output "Fixed: $($file.FullName)"
    }
}
