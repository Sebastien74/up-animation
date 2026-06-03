<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="robots" content="noindex,nofollow"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Accès refusé</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #101129;
            --ink-2: #161834;
            --orange: #ff7100;
            --orange-soft: #ff9a4d;
            --cream: #f3ede2;
            --text: #eceaf6;
            --muted: #9396b9;
            --line: rgba(255, 255, 255, .09);
            --surface: rgba(255, 255, 255, .035);
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
                radial-gradient(58vw 58vw at 82% -8%, rgba(255, 113, 0, .2), transparent 60%),
                radial-gradient(55vw 55vw at 8% 112%, rgba(34, 37, 78, .9), transparent 55%),
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
        .mark svg { width: 26px; height: 26px; color: var(--orange); }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            margin-top: 1.6rem;
            font-size: .78rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--orange-soft);
            font-weight: 600;
            opacity: 0;
            animation: rise .7s .18s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .eyebrow .dot {
            width: 9px; height: 9px; border-radius: 50%;
            background: var(--orange);
            box-shadow: 0 0 12px var(--orange);
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
            opacity: 0;
            animation: rise .7s .26s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .lead {
            font-weight: 300;
            color: var(--muted);
            max-width: 52ch;
            font-size: 1.15rem;
            margin-left: 4rem;
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
        .btn-primary { background: var(--orange); color: #fff; }
        .btn-primary:hover { background: var(--orange-soft); transform: translateY(-2px); }
        .btn-ghost { background: transparent; border-color: var(--line); color: var(--text); }
        .btn-ghost:hover { border-color: var(--orange); color: #fff; transform: translateY(-2px); }
        .btn svg { width: 17px; height: 17px; }

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
<div class="watermark" aria-hidden="true">403</div>
<div class="wrap">

    <span class="mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><path d="M12 16v2"/></svg>
    </span>

    <span class="eyebrow"><span class="dot"></span>Accès refusé · 403</span>

    <h1>Accès<br>refusé.</h1>

    <p class="lead">Vous n'êtes pas autorisé à consulter cette page. L'accès est restreint pour des raisons de sécurité. Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur du site.</p>

    <div class="actions">
        <a href="/" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M9 22V12h6v10"/></svg>
            Retour à l'accueil
        </a>
    </div>

</div>
</body>
</html>
