<?php

$locale = \Locale::getDefault();

$translations['fr_FR'] = [
	'seo_title' => 'Vous êtes hors ligne',
	'eyebrow' => 'Connexion perdue',
	'info' => 'Aucune connexion réseau détectée. Vérifiez votre accès à Internet, puis réessayez de charger la page.',
	'button' => 'Recharger'
];

$translations['en_GB'] = [
	'seo_title' => 'You are offline',
	'eyebrow' => 'Connection lost',
	'info' => 'No network connection detected. Check your internet access, then try loading the page again.',
	'button' => 'Reload'
];

$t = $translations[$locale] ?? $translations['en_GB'];
$title = !empty($t['seo_title']) ? $t['seo_title'] : $translations['en_GB']['seo_title'];
$eyebrow = !empty($t['eyebrow']) ? $t['eyebrow'] : $translations['en_GB']['eyebrow'];
$info = !empty($t['info']) ? $t['info'] : $translations['en_GB']['info'];
$button = !empty($t['button']) ? $t['button'] : $translations['en_GB']['button'];

?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="robots" content="noindex,nofollow"/>
    <title><?= $title ?></title>
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
            font-family: 'Hanken Grotesk', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
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

        .wrap {
            position: relative;
            width: 100%;
            max-width: 720px;
            margin: auto;
            padding: clamp(2.5rem, 7vw, 5rem) clamp(1.25rem, 5vw, 3rem);
            text-align: center;
        }

        .mark {
            width: 92px; height: 92px;
            margin: 0 auto;
            border-radius: 50%;
            background: var(--surface);
            border: 1px solid var(--line);
            display: grid;
            place-items: center;
            position: relative;
            opacity: 0;
            animation: pop .8s .1s cubic-bezier(.2, .8, .2, 1) forwards;
        }
        .mark::before {
            content: "";
            position: absolute;
            inset: -10px;
            border-radius: 50%;
            border: 1px solid rgba(255, 113, 0, .25);
            animation: halo 3.2s ease-in-out infinite;
        }
        .mark svg { width: 42px; height: 42px; color: var(--orange); }

        .eyebrow {
            display: inline-block;
            margin-top: 1.8rem;
            font-size: .78rem;
            letter-spacing: .24em;
            text-transform: uppercase;
            color: var(--orange-soft);
            font-weight: 600;
            opacity: 0;
            animation: rise .7s .24s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        h1 {
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -.01em;
            line-height: .95;
            font-size: clamp(2.4rem, 8vw, 4.2rem);
            margin: .9rem 0;
            color: #fff;
            opacity: 0;
            animation: rise .7s .32s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        p {
            font-weight: 300;
            color: var(--muted);
            max-width: 46ch;
            margin: 0 auto;
            font-size: 1.1rem;
            opacity: 0;
            animation: rise .7s .4s cubic-bezier(.2, .7, .2, 1) forwards;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            min-height: 50px;
            margin-top: 2.2rem;
            padding: 0 1.9rem;
            border: none;
            border-radius: var(--radius-btn);
            background: var(--orange);
            color: #fff;
            font: inherit;
            font-weight: 600;
            font-size: .95rem;
            cursor: pointer;
            transition: background .25s ease, transform .25s ease;
            opacity: 0;
            animation: rise .7s .48s cubic-bezier(.2, .7, .2, 1) forwards;
        }
        .btn:hover { background: var(--orange-soft); transform: translateY(-2px); }
        .btn svg { width: 17px; height: 17px; transition: transform .55s ease; }
        .btn:hover svg { transform: rotate(-180deg); }

        @keyframes rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
        @keyframes pop { from { opacity: 0; transform: scale(.7); } to { opacity: 1; transform: none; } }
        @keyframes halo { 0%, 100% { transform: scale(1); opacity: .8; } 50% { transform: scale(1.1); opacity: .25; } }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; }
            .mark, .eyebrow, h1, p, .btn { opacity: 1 !important; }
        }
    </style>
</head>
<body>
<div class="wrap">

    <span class="mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 2 20 20"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M2 8.82a15 15 0 0 1 4.17-2.65"/><path d="M10.66 5c4.01-.36 8.14.9 11.34 3.76"/><path d="M16.85 11.25a10 10 0 0 1 2.22 1.68"/><path d="M5 13a10 10 0 0 1 5.24-2.76"/><path d="M12 20h.01"/></svg>
    </span>

    <span class="eyebrow"><?= $eyebrow ?></span>
    <h1><?= $title ?></h1>
    <p><?= $info ?></p>

    <button type="button" class="btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
        <?= $button ?>
    </button>

</div>

<script>
    document.querySelector("button").addEventListener("click", () => {
        window.location.reload();
    });
</script>
</body>
</html>
