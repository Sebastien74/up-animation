<?php

declare(strict_types=1);

http_response_code(503);
header('Retry-After: 3600');
header('X-Robots-Tag: noindex, nofollow, noarchive');

?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Site en maintenance</title>
        <link rel="icon" type="image/png" href="/medias/favicons/maintenance/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/medias/favicons/maintenance/favicon.svg" />
        <link rel="shortcut icon" href="/medias/favicons/maintenance/favicon.ico" />
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
                --hl: #fbbf24;
                --hl-soft: #fde68a;
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
                    radial-gradient(58vw 58vw at 82% -8%, color-mix(in srgb, var(--hl) 18%, transparent), transparent 60%),
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
                font-size: 44vw;
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

            .worktrack {
                margin: 2.5rem 0 0 4rem;
                max-width: 44rem;
                opacity: 0;
                animation: rise .7s .42s cubic-bezier(.2, .7, .2, 1) forwards;
            }
            .worktrack .cap {
                display: flex;
                flex-wrap: wrap;
                align-items: baseline;
                justify-content: space-between;
                gap: .5rem 1rem;
                font-family: 'IBM Plex Mono', monospace;
                font-size: .76rem;
                letter-spacing: .04em;
                color: var(--muted);
                margin-bottom: .7rem;
            }
            .worktrack .cap b { color: var(--bright); font-weight: 500; }
            .worktrack .bar {
                position: relative;
                height: 12px;
                border-radius: 100px;
                border: 1px solid var(--line);
                background: var(--surface);
                overflow: hidden;
            }
            .worktrack .bar::before {
                content: "";
                position: absolute;
                inset: 0;
                background: repeating-linear-gradient(45deg,
                    var(--hl) 0 10px,
                    color-mix(in srgb, var(--hl) 20%, transparent) 10px 20px);
                animation: hazard .8s linear infinite;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: .8rem;
                margin: 2.5rem 0 0 4rem;
                opacity: 0;
                animation: rise .7s .5s cubic-bezier(.2, .7, .2, 1) forwards;
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
                background: var(--hl);
                color: #241803;
                transition: background .25s ease, transform .25s ease;
            }
            .btn:hover { background: var(--hl-soft); transform: translateY(-2px); }
            .btn:focus-visible { outline: 2px solid var(--hl-soft); outline-offset: 3px; }
            .btn svg { width: 17px; height: 17px; transition: transform .55s ease; }
            .btn:hover svg { transform: rotate(-180deg); }

            @keyframes rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
            @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
            @keyframes hazard { to { background-position: 28.284px 0; } }

            @media (max-width: 600px) {
                .lead, .worktrack, .actions { margin-left: 0; }
                .watermark { font-size: 70vw; }
            }
            @media (prefers-reduced-motion: reduce) {
                * { animation: none !important; }
                .mark, .eyebrow, h1, .lead, .worktrack, .actions { opacity: 1 !important; }
            }
        </style>
    </head>
    <body>
        <div class="watermark" aria-hidden="true">503</div>
        <div class="wrap">

            <span class="mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </span>

            <span class="eyebrow"><span class="dot"></span>Maintenance · en cours</span>

            <h1>Le site est<br>en maintenance.</h1>

            <p class="lead">Nous effectuons une mise à jour pour améliorer le site. Il sera de nouveau accessible dans quelques instants. Merci de votre patience.</p>

            <div class="worktrack" role="presentation">
                <div class="cap"><span><b>Mise à jour</b> en cours</span><span>503 · Service temporairement indisponible</span></div>
                <div class="bar" aria-hidden="true"></div>
            </div>

            <div class="actions">
                <button type="button" class="btn" onclick="location.reload()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                    Réessayer
                </button>
            </div>

        </div>
    </body>
</html>
