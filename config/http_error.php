<?php

/**
 * Standalone, dependency-free HTTP error page sharing the visual identity of
 * config/platform_error.php. Rendered for ?simule_error=<code> (dev helper) and
 * usable as a Twig/Webpack-free fallback. Expects $statusCode (int) in scope,
 * optionally $detail (?string), $homeUrl (?string) and $isDebug (bool).
 */

declare(strict_types=1);

$statusCode = isset($statusCode) ? (int) $statusCode : 500;
$detail = isset($detail) && '' !== (string) $detail ? (string) $detail : null;
$homeUrl = isset($homeUrl) && '' !== (string) $homeUrl ? (string) $homeUrl : '/';
$isDebug = !empty($isDebug);

$copy = [
    400 => ['headline' => ['La requête est ', 'invalide', '.'], 'lede' => "La requête envoyée n'a pas pu être interprétée par le serveur."],
    403 => ['headline' => ["L'accès vous est ", 'refusé', '.'], 'lede' => "Vous n'avez pas l'autorisation d'accéder à cette ressource."],
    404 => ['headline' => ['Cette page est ', 'introuvable', '.'], 'lede' => "La page demandée n'existe pas, a été déplacée ou n'est plus disponible."],
    500 => ['headline' => ['Une erreur ', 'interne', ' est survenue.'], 'lede' => "Une erreur inattendue s'est produite sur le serveur. Nos équipes ont été informées et travaillent à sa résolution."],
    503 => ['headline' => ['Le service est ', 'indisponible', '.'], 'lede' => 'Le service est temporairement indisponible. Merci de réessayer dans quelques instants.'],
];
$labels = [400 => 'Requête invalide', 403 => 'Accès refusé', 404 => 'Page introuvable', 500 => 'Erreur interne', 503 => 'Service indisponible'];

$current = $copy[$statusCode] ?? ['headline' => ['Une ', 'erreur', ' est survenue.'], 'lede' => "Une erreur inattendue s'est produite."];
$statusLabel = $labels[$statusCode] ?? 'Erreur';
$headline = $current['headline'];

$e = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    http_response_code($statusCode);
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Erreur <?= $e((string) $statusCode) ?> &middot; <?= $e($statusLabel) ?></title>
<link rel="icon" type="image/png" href="/medias/favicons/error/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/medias/favicons/error/favicon.svg" />
<link rel="shortcut icon" href="/medias/favicons/error/favicon.ico" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700;800&display=swap" rel="stylesheet">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { -webkit-text-size-adjust: 100%; }

    body {
        min-height: 100vh;
        font-family: "JetBrains Mono", ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
        color: #e7e3da;
        background-color: #0b0c0f;
        background-image:
            radial-gradient(120% 90% at 18% -10%, rgba(233, 162, 59, 0.16) 0%, rgba(233, 162, 59, 0) 55%),
            radial-gradient(90% 70% at 100% 0%, rgba(229, 72, 77, 0.10) 0%, rgba(229, 72, 77, 0) 50%),
            linear-gradient(180deg, #0d0e12 0%, #08090c 100%);
        background-attachment: fixed;
        line-height: 1.6;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 6vw 6vw 8vw;
        position: relative;
        overflow-x: hidden;
    }

    /* Fine engineering grid */
    body::before {
        content: "";
        position: fixed;
        inset: 0;
        background-image:
            linear-gradient(rgba(231, 227, 218, 0.035) 1px, transparent 1px),
            linear-gradient(90deg, rgba(231, 227, 218, 0.035) 1px, transparent 1px);
        background-size: 72px 72px;
        -webkit-mask-image: radial-gradient(120% 100% at 30% 0%, #000 0%, transparent 75%);
        mask-image: radial-gradient(120% 100% at 30% 0%, #000 0%, transparent 75%);
        pointer-events: none;
        z-index: 0;
    }

    /* Film grain */
    body::after {
        content: "";
        position: fixed;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.5'/%3E%3C/svg%3E");
        opacity: 0.04;
        mix-blend-mode: overlay;
        pointer-events: none;
        z-index: 1;
    }

    main {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 680px;
    }

    .eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.7em;
        font-size: 0.72rem;
        letter-spacing: 0.34em;
        text-transform: uppercase;
        color: #9a9488;
        opacity: 0;
        animation: rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.05s forwards;
    }

    .pulse {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #e5484d;
        box-shadow: 0 0 0 0 rgba(229, 72, 77, 0.55);
        animation: pulse 2.4s ease-out infinite;
    }

    h1 {
        font-family: "JetBrains Mono", ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
        font-weight: 800;
        font-size: clamp(1.85rem, 5vw, 3.1rem);
        line-height: 1.12;
        letter-spacing: -0.04em;
        color: #f4f1ea;
        margin: 1.4rem 0 0;
        opacity: 0;
        animation: rise 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.14s forwards;
    }

    h1 em {
        font-style: normal;
        color: #e9a23b;
    }

    .lede {
        margin-top: 1.4rem;
        max-width: 46ch;
        font-size: 0.95rem;
        color: #b6b1a6;
        opacity: 0;
        animation: rise 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.24s forwards;
    }

    .panel {
        margin-top: 2.6rem;
        border: 1px solid rgba(231, 227, 218, 0.10);
        border-radius: 14px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.025), rgba(255, 255, 255, 0.008));
        backdrop-filter: blur(2px);
        overflow: hidden;
        opacity: 0;
        animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.34s forwards;
    }

    .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid rgba(231, 227, 218, 0.08);
        font-size: 0.7rem;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: #8f897d;
    }

    .panel-head .dots { display: inline-flex; gap: 6px; }
    .panel-head .dots span {
        width: 9px; height: 9px; border-radius: 50%;
        background: rgba(231, 227, 218, 0.16);
    }
    .panel-head .dots span:first-child { background: #e5484d; }

    .check {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 0.6rem 1.5rem;
        padding: 1.15rem 1.25rem;
    }
    .check + .check { border-top: 1px dashed rgba(231, 227, 218, 0.09); }

    .check-label {
        font-size: 0.7rem;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #8f897d;
    }

    .versions {
        grid-column: 1 / -1;
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        font-size: 1.05rem;
    }

    .v-code {
        font-size: clamp(2.6rem, 9vw, 4rem);
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.04em;
        color: #e5484d;
    }
    .v-status {
        color: #f4f1ea;
        font-weight: 700;
        padding: 0.05em 0.55em;
        border-radius: 6px;
        background: rgba(233, 162, 59, 0.14);
        box-shadow: inset 0 0 0 1px rgba(233, 162, 59, 0.35);
    }

    .badge {
        justify-self: end;
        align-self: start;
        font-size: 0.62rem;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        padding: 0.3em 0.7em;
        border-radius: 999px;
        white-space: nowrap;
        color: #ffb4b6;
        background: rgba(229, 72, 77, 0.14);
        box-shadow: inset 0 0 0 1px rgba(229, 72, 77, 0.4);
    }

    .actions {
        margin-top: 1.9rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.9rem;
        opacity: 0;
        animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.46s forwards;
    }
    .btn-home {
        display: inline-flex;
        align-items: center;
        gap: 0.6em;
        font: inherit;
        font-size: 0.8rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        text-decoration: none;
        color: #0b0c0f;
        background: #e9a23b;
        padding: 0.85em 1.4em;
        border-radius: 9px;
        font-weight: 700;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .btn-home:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(233, 162, 59, 0.25); }
    .btn-home .mark { font-size: 1.1em; }

    .raw {
        margin-top: 1.9rem;
        opacity: 0;
        animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.56s forwards;
    }
    .raw summary {
        cursor: pointer;
        list-style: none;
        font-size: 0.72rem;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #6f6a5f;
        transition: color 0.2s ease;
        user-select: none;
    }
    .raw summary:hover { color: #9a9488; }
    .raw summary::-webkit-details-marker { display: none; }
    .raw summary::before { content: "+ "; color: #e9a23b; }
    .raw[open] summary::before { content: "- "; }
    .raw pre {
        margin-top: 0.9rem;
        padding: 1rem 1.1rem;
        border-radius: 10px;
        border: 1px solid rgba(231, 227, 218, 0.08);
        background: rgba(0, 0, 0, 0.35);
        color: #b6b1a6;
        font-size: 0.78rem;
        line-height: 1.7;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .cursor {
        display: inline-block;
        width: 0.6ch;
        height: 1em;
        margin-left: 0.15ch;
        background: #e9a23b;
        transform: translateY(0.12em);
        animation: blink 1.1s steps(1) infinite;
    }

    @keyframes rise {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(229, 72, 77, 0.5); }
        70% { box-shadow: 0 0 0 12px rgba(229, 72, 77, 0); }
        100% { box-shadow: 0 0 0 0 rgba(229, 72, 77, 0); }
    }
    @keyframes blink { 0%, 50% { opacity: 1; } 50.01%, 100% { opacity: 0; } }

    @media (prefers-reduced-motion: reduce) {
        * { animation: none !important; }
        .eyebrow, h1, .lede, .panel, .actions, .raw { opacity: 1; }
    }
</style>
</head>
<body>
<main>
    <span class="eyebrow"><span class="pulse"></span>Erreur &middot; <?= $e((string) $statusCode) ?></span>

    <h1><?= $e($headline[0]) ?><em><?= $e($headline[1]) ?></em><?= $e($headline[2]) ?></h1>

    <p class="lede"><?= $e($current['lede']) ?></p>

    <section class="panel" aria-label="Statut HTTP">
        <div class="panel-head">
            <span>http status</span>
            <span class="dots"><span></span><span></span><span></span></span>
        </div>
        <div class="check">
            <span class="check-label">Code</span>
            <span class="badge"><?= $e($statusLabel) ?></span>
            <div class="versions">
                <span class="v-code"><?= $e((string) $statusCode) ?></span>
                <span class="v-status"><?= $e($statusLabel) ?></span>
            </div>
        </div>
    </section>

    <div class="actions">
        <a href="<?= $e($homeUrl) ?>" class="btn-home"><span class="mark">&#9656;</span> Retour à l'accueil</a>
    </div>

    <?php if ($isDebug && null !== $detail): ?>
    <details class="raw" open>
        <summary>Détail technique</summary>
        <pre><?= $e($detail) ?><span class="cursor"></span></pre>
    </details>
    <?php endif; ?>
</main>
</body>
</html>
