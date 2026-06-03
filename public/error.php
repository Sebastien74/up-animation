<?php

header('X-Robots-Tag: noindex, nofollow, noarchive');

$ips = ['::1', '127.0.0.1', 'fe80::1', '194.51.155.21', '195.135.16.88', '176.135.112.19', '2001:861:43c3:ce70:448f:74b:e526:cdae', '2001:861:43c3:ce70:60b8:f71:1c9:4843'];
$allowed = in_array($_SERVER['REMOTE_ADDR'] ?? '', $ips, true);
if (!$allowed) {
    header('HTTP/1.0 403 Forbidden');
    require_once $_SERVER['DOCUMENT_ROOT'] . '/denied.php';
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="robots" content="noindex,nofollow"/>
    <title>Erreur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0e1014;
            --ink-2: #14171d;
            --text: #e6e8ee;
            --bright: #f5f6f8;
            --muted: #9aa0ad;
            --line: rgba(255, 255, 255, .08);
            --surface: rgba(255, 255, 255, .04);
            --hl: #f87171;
            --hl-soft: #fca5a5;
            --radius-btn: 20px;
        }

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
            display: flex;
        }

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

        .watermark {
            position: fixed;
            top: 50%;
            right: -3vw;
            transform: translateY(-50%);
            font-weight: 900;
            font-size: 46vw;
            line-height: 1;
            letter-spacing: -.06em;
            color: rgba(255, 255, 255, .02);
            user-select: none;
            pointer-events: none;
            z-index: -1;
            white-space: nowrap;
        }

        .wrap {
            position: relative;
            width: 100%;
            max-width: 960px;
            margin: auto;
            padding: clamp(2.5rem, 7vw, 5rem) clamp(1.25rem, 5vw, 3rem);
        }

        .mark {
            width: 52px; height: 52px;
            border-radius: 15px;
            background: var(--surface);
            border: 1px solid var(--line);
            display: grid;
            place-items: center;
            opacity: 0;
            animation: rise .7s .05s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .mark svg { width: 26px; height: 26px; color: var(--hl); }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            margin-top: 1.6rem;
            font-size: .78rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--hl-soft);
            font-weight: 600;
            opacity: 0;
            animation: rise .7s .18s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .eyebrow .dot {
            width: 9px; height: 9px; border-radius: 50%;
            background: var(--hl);
            box-shadow: 0 0 12px var(--hl);
            animation: blink 2.4s ease-in-out infinite;
        }

        h1 {
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -.02em;
            line-height: 1.02;
            font-size: clamp(2.4rem, 7.5vw, 5.25rem);
            margin: 1rem 0 1.2rem;
            color: #fff;
            overflow-wrap: break-word;
            opacity: 0;
            animation: rise .7s .26s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .lead {
            font-weight: 300;
            color: var(--muted);
            max-width: 52ch;
            font-size: 1.15rem;
            margin-left: 4rem;
            overflow-wrap: break-word;
            opacity: 0;
            animation: rise .7s .34s cubic-bezier(.2, .7, .2, 1) forwards;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: .8rem;
            margin: 2.5rem 0 0 4rem;
            opacity: 0;
            animation: rise .7s .42s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            min-height: 50px;
            padding: 0 1.7rem;
            border-radius: var(--radius-btn);
            font: inherit;
            font-weight: 600;
            font-size: .95rem;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: background .25s ease, transform .25s ease, border-color .25s ease, color .25s ease;
        }
        .btn-primary { background: var(--hl); color: #1a0e0e; }
        .btn-primary:hover { background: var(--hl-soft); transform: translateY(-2px); }
        .btn-ghost { background: transparent; border-color: var(--line); color: var(--text); }
        .btn-ghost:hover { border-color: var(--hl); color: #fff; transform: translateY(-2px); }
        .btn svg { width: 17px; height: 17px; }
        .btn-primary svg { transition: transform .55s ease; }
        .btn-primary:hover svg { transform: rotate(-180deg); }

        @keyframes rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }

        @media (max-width: 600px) {
            .lead, .actions { margin-left: 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; }
            .mark, .eyebrow, h1, .lead, .actions { opacity: 1 !important; }
        }
    </style>
</head>
<body>
<div class="watermark" aria-hidden="true">500</div>
<div class="wrap">

    <span class="mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8 2 1.88 1.88M14.12 3.88 16 2"/><path d="M9 7.13v-1a3.003 3.003 0 1 1 6 0v1"/><path d="M12 20c-3.3 0-6-2.7-6-6v-3a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v3c0 3.3-2.7 6-6 6Z"/><path d="M12 20v-9M6.53 9C4.6 8.8 3 7.1 3 5M6 13H2M3 21c0-2.1 1.7-3.9 3.8-4"/><path d="M20.97 5c0 2.1-1.6 3.8-3.5 4M22 13h-4M17.2 17c2.1.1 3.8 1.9 3.8 4"/></svg>
    </span>

    <span class="eyebrow"><span class="dot"></span>Erreur serveur</span>

    <h1>Une erreur<br>est survenue.</h1>

    <p class="lead">Le service a rencontré un problème inattendu. Réessayez dans quelques instants&nbsp;: si le souci persiste, revenez un peu plus tard.</p>

    <div class="actions">
        <button type="button" class="btn btn-primary" onclick="location.reload()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            Recharger la page
        </button>
        <a href="/" class="btn btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M9 22V12h6v10"/></svg>
            Retour à l'accueil
        </a>
    </div>

</div>
</body>
</html>
