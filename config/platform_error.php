<?php

/**
 * Standalone, dependency-free error page rendered when Composer's platform
 * check fails (e.g. PHP version mismatch) before Symfony can boot.
 *
 * It is included from public/index.php and must not rely on the autoloader,
 * Twig or the Webpack build. Expects a $platformException (\Throwable) in scope.
 */

declare(strict_types=1);

/** @var \Throwable $platformException */
$message = isset($platformException) ? $platformException->getMessage() : '';

$current = PHP_VERSION;
$required = null;
if (preg_match('/version\s+">=?\s*([0-9.]+)"/', $message, $m)) {
    $required = $m[1];
}
$needs64bit = str_contains($message, '64-bit');

$checks = [];
$checks[] = [
    'label' => 'Version PHP',
    'ok' => $required === null || version_compare($current, $required, '>='),
    'current' => $current,
    'required' => $required !== null ? '>= ' . $required : null,
];
if ($needs64bit) {
    $checks[] = [
        'label' => 'Architecture',
        'ok' => PHP_INT_SIZE === 8,
        'current' => (PHP_INT_SIZE * 8) . '-bit',
        'required' => '64-bit',
    ];
}

$e = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    http_response_code(500);
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Environnement incompatible</title>
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

    .v-current { color: #e5484d; font-weight: 500; }
    .v-current.is-ok { color: #6bbf73; }
    .arrow { color: #5c574d; }
    .v-required {
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
    }
    .badge.fail { color: #ffb4b6; background: rgba(229, 72, 77, 0.14); box-shadow: inset 0 0 0 1px rgba(229, 72, 77, 0.4); }
    .badge.ok { color: #a7e0ad; background: rgba(107, 191, 115, 0.14); box-shadow: inset 0 0 0 1px rgba(107, 191, 115, 0.4); }

    .hint {
        margin-top: 1.9rem;
        display: flex;
        gap: 0.9rem;
        font-size: 0.85rem;
        color: #9a9488;
        opacity: 0;
        animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.46s forwards;
    }
    .hint .mark { color: #e9a23b; flex: none; }
    .hint code {
        color: #e7e3da;
        background: rgba(231, 227, 218, 0.07);
        padding: 0.1em 0.45em;
        border-radius: 5px;
        font-size: 0.92em;
    }

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
        .eyebrow, h1, .lede, .panel, .hint, .raw { opacity: 1; }
    }
</style>
</head>
<body>
<main>
    <span class="eyebrow"><span class="pulse"></span>Diagnostic · Environnement</span>

    <h1>L'environnement d'exécution<br>n'est pas <em>compatible</em>.</h1>

    <p class="lede">
        L'application n'a pas pu démarrer : la plateforme PHP courante ne satisfait pas
        les prérequis déclarés par le projet.
    </p>

    <section class="panel" aria-label="Contrôles de plateforme">
        <div class="panel-head">
            <span>platform check</span>
            <span class="dots"><span></span><span></span><span></span></span>
        </div>
        <?php foreach ($checks as $check): ?>
        <div class="check">
            <span class="check-label"><?= $e($check['label']) ?></span>
            <span class="badge <?= $check['ok'] ? 'ok' : 'fail' ?>"><?= $check['ok'] ? 'OK' : 'Échec' ?></span>
            <div class="versions">
                <span class="v-current <?= $check['ok'] ? 'is-ok' : '' ?>"><?= $e($check['current']) ?></span>
                <?php if ($check['required'] !== null): ?>
                    <span class="arrow">&rarr;</span>
                    <span class="v-required"><?= $e($check['required']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <p class="hint">
        <span class="mark">&#9656;</span>
        <span>
            Sous WampServer, bascule la version de PHP du virtual host vers
            <code>>= <?= $e($required ?? '8.5') ?></code> (icône Wamp &rarr; PHP &rarr; Version),
            puis recharge la page.
        </span>
    </p>

    <details class="raw">
        <summary>Détail technique</summary>
        <pre><?= $e($message) ?><span class="cursor"></span></pre>
    </details>
</main>
</body>
</html>
