<?php

header('X-Robots-Tag: noindex, nofollow, noarchive');

$ips = ['::1', '127.0.0.1', 'fe80::1', '194.51.155.21', '195.135.16.88', '176.135.112.19', '2001:861:43c3:ce70:448f:74b:e526:cdae', '2001:861:43c3:ce70:60b8:f71:1c9:4843'];
// Trust only the real TCP peer; X-Forwarded-For is client-spoofable.
$allowed = in_array($_SERVER['REMOTE_ADDR'] ?? '', $ips, true);
if (!$allowed) {
    header('HTTP/1.0 403 Forbidden');
    require_once $_SERVER['DOCUMENT_ROOT'] . '/denied.php';
    exit;
}

use Symfony\Requirements\SymfonyRequirements;

if (!isset($_SERVER['HTTP_HOST'])) {
    exit("This script cannot be run from the CLI. Run it from a browser.\n");
}

$autoloader = __DIR__.'/../vendor/autoload.php';
require_once $autoloader;

$symfonyVersion = class_exists('\Symfony\Component\HttpKernel\Kernel') ? \Symfony\Component\HttpKernel\Kernel::VERSION : null;

$symfonyRequirements = new SymfonyRequirements(dirname(dirname(realpath($autoloader))), $symfonyVersion);

$majorProblems = $symfonyRequirements->getFailedRequirements();
$minorProblems = $symfonyRequirements->getFailedRecommendations();
$hasMajorProblems = (bool) count($majorProblems);
$hasMinorProblems = (bool) count($minorProblems);

$state = $hasMajorProblems ? 'critical' : ($hasMinorProblems ? 'warning' : 'ready');
$stateLabel = ['ready' => 'Système prêt', 'warning' => 'À surveiller', 'critical' => 'Action requise'][$state];
$watermark = $hasMajorProblems ? 'KO' : 'OK';

?>
<!DOCTYPE html>
<html lang="fr" data-state="<?= $state ?>">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="robots" content="noindex,nofollow"/>
    <title>Vérification système</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@300;400;500;600;700;900&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0e1014;
            --ink-2: #14171d;
            --text: #e6e8ee;
            --bright: #f5f6f8;
            --muted: #9aa0ad;
            --line: rgba(255, 255, 255, .08);
            --surface: rgba(255, 255, 255, .04);
            --ok: #4ade80;
            --warning: #fbbf24;
            --danger: #f87171;
            --info: #60a5fa;
            --hl: var(--info);
            --hl-soft: #93c5fd;
            --radius: 28px;
            --radius-btn: 20px;
        }
        [data-state="ready"]    { --hl: #4ade80; --hl-soft: #86efac; }
        [data-state="warning"]  { --hl: #fbbf24; --hl-soft: #fde68a; }
        [data-state="critical"] { --hl: #f87171; --hl-soft: #fca5a5; }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-text-size-adjust: 100%; }

        body {
            font-family: 'Hanken Grotesk', sans-serif;
            color: var(--text);
            background: var(--ink);
            min-height: 100dvh;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            position: relative;
            overflow-x: hidden;
        }

        /* Atmosphere: subtle accent glow tinted by the current state + neutral fill */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background:
                radial-gradient(58vw 58vw at 82% -8%, color-mix(in srgb, var(--hl) 20%, transparent), transparent 60%),
                radial-gradient(55vw 55vw at 8% 112%, rgba(255, 255, 255, .035), transparent 55%),
                linear-gradient(180deg, var(--ink), var(--ink-2));
            z-index: -2;
        }

        /* Fine grain overlay */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.5'/%3E%3C/svg%3E");
            opacity: .045;
            mix-blend-mode: screen;
            pointer-events: none;
            z-index: -1;
        }

        /* Oversized status watermark */
        .watermark {
            position: fixed;
            top: 50%;
            right: -2vw;
            transform: translateY(-50%);
            font-weight: 900;
            font-size: 44vw;
            line-height: 1;
            letter-spacing: -.05em;
            color: rgba(255, 255, 255, .018);
            user-select: none;
            pointer-events: none;
            z-index: -1;
            white-space: nowrap;
        }

        .wrap {
            position: relative;
            max-width: 960px;
            margin: 0 auto;
            padding: clamp(2rem, 5vw, 4.5rem) clamp(1.25rem, 4vw, 2.5rem) 4rem;
        }

        /* ---- Masthead ---- */
        .masthead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding-bottom: 2.25rem;
            border-bottom: 1px solid var(--line);
            opacity: 0;
            animation: rise .7s .05s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .brand { display: flex; align-items: center; gap: 1rem; }
        .mark {
            width: 46px; height: 46px;
            border-radius: 14px;
            background: var(--surface);
            border: 1px solid var(--line);
            display: grid;
            place-items: center;
            flex: none;
        }
        .mark svg { width: 23px; height: 23px; color: var(--hl); }
        .brand .tag {
            font-size: .66rem;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: var(--muted);
            line-height: 1.4;
        }
        .versions {
            font-family: 'IBM Plex Mono', monospace;
            font-size: .72rem;
            letter-spacing: .04em;
            color: var(--muted);
            text-align: right;
            white-space: nowrap;
        }
        .versions b { color: var(--bright); font-weight: 500; }

        /* ---- Hero (editorial offset) ---- */
        .hero { padding: clamp(2.5rem, 7vw, 5rem) 0 1.5rem; }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            font-size: .78rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--hl-soft);
            font-weight: 600;
            opacity: 0;
            animation: rise .7s .2s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .eyebrow .dot {
            width: 9px; height: 9px; border-radius: 50%;
            background: var(--hl);
            box-shadow: 0 0 12px var(--hl);
            animation: blink 2.4s ease-in-out infinite;
        }

        .hero h1 {
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -.02em;
            line-height: 1.02;
            font-size: clamp(2.4rem, 7vw, 4.75rem);
            margin: 1rem 0 1.1rem;
            color: #fff;
            overflow-wrap: break-word;
            opacity: 0;
            animation: rise .7s .28s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .hero p {
            font-weight: 300;
            color: var(--muted);
            max-width: 50ch;
            font-size: 1.1rem;
            margin-left: 4rem;
            opacity: 0;
            animation: rise .7s .36s cubic-bezier(.2, .7, .2, 1) forwards;
        }

        /* ---- Summary pills ---- */
        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            margin: 2.25rem 0 3rem;
            opacity: 0;
            animation: rise .7s .44s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            padding: .6rem 1.15rem;
            border-radius: 100px;
            background: var(--surface);
            border: 1px solid var(--line);
            font-size: .9rem;
        }
        .pill .dot { width: 8px; height: 8px; border-radius: 50%; flex: none; }
        .pill b { font-variant-numeric: tabular-nums; font-weight: 700; color: #fff; }
        .pill .lbl { color: var(--muted); }
        .dot.ok { background: var(--ok); box-shadow: 0 0 10px color-mix(in srgb, var(--ok) 55%, transparent); }
        .dot.danger { background: var(--danger); box-shadow: 0 0 10px color-mix(in srgb, var(--danger) 55%, transparent); }
        .dot.warning { background: var(--warning); box-shadow: 0 0 10px color-mix(in srgb, var(--warning) 55%, transparent); }

        /* ---- Sections ---- */
        section { margin-bottom: 2.5rem; }
        .sec-head { display: flex; align-items: baseline; gap: .85rem; margin-bottom: 1.15rem; }
        .sec-head h2 { font-size: 1.05rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .sec-head .count { font-family: 'IBM Plex Mono', monospace; font-size: .8rem; color: var(--muted); }
        .sec-head .rule { flex: 1; height: 1px; background: var(--line); }

        .item {
            position: relative;
            padding: 1.2rem 1.4rem 1.2rem 1.6rem;
            border-radius: 18px;
            background: var(--surface);
            border: 1px solid var(--line);
            margin-bottom: .75rem;
            overflow: hidden;
        }
        .item::before {
            content: "";
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: var(--rail, var(--warning));
        }
        .item.major { --rail: var(--danger); }
        .item .msg { font-weight: 600; color: var(--bright); }
        .item .help {
            margin-top: .45rem;
            font-family: 'IBM Plex Mono', monospace;
            font-size: .82rem;
            line-height: 1.65;
            color: var(--muted);
            word-break: break-word;
        }
        .item .help a { color: var(--hl-soft); text-decoration: none; border-bottom: 1px solid color-mix(in srgb, var(--hl-soft) 40%, transparent); }
        .item .help a:hover { color: #fff; }
        .item .help strong { color: var(--text); font-weight: 600; }

        /* ---- Success banner ---- */
        .allgood {
            display: flex;
            align-items: center;
            gap: 1.1rem;
            padding: 1.5rem 1.7rem;
            border-radius: var(--radius);
            background: linear-gradient(120deg, color-mix(in srgb, var(--ok) 10%, transparent), transparent);
            border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);
            color: var(--bright);
            font-size: 1.08rem;
            opacity: 0;
            animation: rise .7s .5s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .allgood svg { color: var(--ok); flex: none; width: 30px; height: 30px; }

        /* ---- Footer ---- */
        footer {
            margin-top: 3rem;
            padding-top: 1.75rem;
            border-top: 1px solid var(--line);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            min-height: 50px;
            padding: 0 1.7rem;
            border: 1px solid color-mix(in srgb, var(--hl) 45%, transparent);
            border-radius: var(--radius-btn);
            background: color-mix(in srgb, var(--hl) 14%, transparent);
            color: #fff;
            font: inherit;
            font-weight: 600;
            font-size: .95rem;
            cursor: pointer;
            text-decoration: none;
            transition: background .25s ease, transform .25s ease, border-color .25s ease;
        }
        .btn:hover { background: color-mix(in srgb, var(--hl) 30%, transparent); border-color: var(--hl); transform: translateY(-2px); }
        .btn svg { width: 17px; height: 17px; transition: transform .55s ease; }
        .btn:hover svg { transform: rotate(-180deg); }
        .footnote {
            font-family: 'IBM Plex Mono', monospace;
            font-size: .76rem;
            color: var(--muted);
            text-align: right;
        }
        .footnote b { color: var(--bright); font-weight: 500; }

        @keyframes rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }

        @media (max-width: 600px) {
            .hero p { margin-left: 0; }
            .masthead { flex-direction: column; align-items: flex-start; }
            .versions { text-align: left; }
            .watermark { font-size: 70vw; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; }
            .masthead, .eyebrow, .hero h1, .hero p, .summary, .allgood { opacity: 1 !important; }
        }
    </style>
</head>
<body>
<div class="watermark" aria-hidden="true"><?= $watermark ?></div>
<div class="wrap">

    <header class="masthead">
        <div class="brand">
            <span class="mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M15 2v2M15 20v2M2 15h2M2 9h2M20 15h2M20 9h2M9 2v2M9 20v2"/></svg>
            </span>
            <span class="tag">Diagnostic<br>système</span>
        </div>
        <div class="versions">
            <b>PHP <?= PHP_VERSION ?></b><br>
            Symfony <?= $symfonyVersion ?: 'n/c' ?>
        </div>
    </header>

    <div class="hero">
        <span class="eyebrow"><span class="dot"></span><?= $stateLabel ?></span>
        <h1><?php if ($state === 'ready'): ?>Tout est<br>en ordre.<?php elseif ($state === 'critical'): ?>Configuration<br>à corriger.<?php else: ?>Presque<br>prêt.<?php endif; ?></h1>
        <p>Cette page analyse votre environnement pour vérifier qu'il est prêt à exécuter l'application Symfony.</p>
    </div>

    <div class="summary">
        <span class="pill"><span class="dot danger"></span><b><?= count($majorProblems) ?></b> <span class="lbl">problème(s) majeur(s)</span></span>
        <span class="pill"><span class="dot warning"></span><b><?= count($minorProblems) ?></b> <span class="lbl">recommandation(s)</span></span>
        <?php if ($state === 'ready'): ?>
            <span class="pill"><span class="dot ok"></span><span class="lbl">Tous les contrôles passés</span></span>
        <?php endif; ?>
    </div>

    <?php if ($hasMajorProblems): ?>
        <section>
            <div class="sec-head"><h2>Problèmes majeurs</h2><span class="count"><?= count($majorProblems) ?></span><span class="rule"></span></div>
            <?php foreach ($majorProblems as $problem): ?>
                <div class="item major">
                    <div class="msg"><?= $problem->getTestMessage() ?></div>
                    <div class="help"><?= $problem->getHelpHtml() ?></div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($hasMinorProblems): ?>
        <section>
            <div class="sec-head"><h2>Recommandations</h2><span class="count"><?= count($minorProblems) ?></span><span class="rule"></span></div>
            <?php foreach ($minorProblems as $problem): ?>
                <div class="item">
                    <div class="msg"><?= $problem->getTestMessage() ?></div>
                    <div class="help"><?= $problem->getHelpHtml() ?></div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if (!$hasMajorProblems && !$hasMinorProblems): ?>
        <div class="allgood">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            <div>Tous les contrôles sont passés avec succès. Votre système est prêt à exécuter l'application.</div>
        </div>
    <?php endif; ?>

    <footer>
        <?php if ($hasMajorProblems || $hasMinorProblems): ?>
            <button type="button" class="btn" onclick="location.reload()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                Relancer la vérification
            </button>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
        <div class="footnote"><b><?= date('d/m/Y H:i') ?></b></div>
    </footer>

</div>
</body>
</html>
