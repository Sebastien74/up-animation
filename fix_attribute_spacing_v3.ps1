$files = Get-ChildItem -Path templates -Filter *.html.twig -Recurse

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $originalContent = $content

    # Passe 1: convertir {%- ... -%} -> {% ... %} uniquement à l'intérieur des attributs
    $attrRegex = '([a-zA-Z0-9:-]+)="([^"]*?)"'
    $content = [System.Text.RegularExpressions.Regex]::Replace($content, $attrRegex, {
        param($m)
        $attrName = $m.Groups[1].Value
        $attrValue = $m.Groups[2].Value

        $newValue = $attrValue
        if ($newValue -match '\{%-|-%\}') {
            $newValue = $newValue.Replace('{%-', '{%').Replace('-%}', '%}')
        }
        # Trim des espaces de début/fin à l'intérieur des attributs (évite id=" main-preloader" et class="foo ")
        $newValue = $newValue.Trim()
        return "$attrName=""$newValue"""
    })

    if ($content -ne $originalContent) {
        [System.IO.File]::WriteAllText($file.FullName, $content, (New-Object System.Text.UTF8Encoding($false)))
        Write-Output "Fixed: $($file.FullName)"
    }
}
