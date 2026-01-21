<?php

$locale = \Locale::getDefault();

$translations['fr_FR'] = [
	'seo_title' => 'Vous êtes hors ligne',
	'info' => 'Cliquez sur le bouton ci-dessous pour essayer de recharger.',
	'button' => 'Recharger'
];

$translations['en_GB'] = [
	'seo_title' => 'You are offline',
	'info' => 'Click the button below to try reloading.',
	'button' => 'Reload'
];

$title = !empty($translations[$locale]['seo_title']) ? $translations[$locale]['seo_title'] : $translations['en_GB']['seo_title'];
$info = !empty($translations[$locale]['info']) ? $translations[$locale]['info'] : $translations['en_GB']['info'];
$button = !empty($translations[$locale]['button']) ? $translations[$locale]['button'] : $translations['en_GB']['button'];

?>

<!DOCTYPE html>
<html lang="<?= $locale ?>">

<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $title ?></title>
	<?php require './service-worker/style.html' ?>
</head>

<body>

<div id="content" class="container-fluid">
    <div class="row text-center">
        <div class="col-md-4 offset-md-4 my-auto">
            <svg id="power-off" class="mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!-- Font Awesome Pro 5.15.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><defs><style>.fa-secondary{opacity:.4}</style></defs><path d="M272 0a23.94 23.94 0 0 1 24 24v240a23.94 23.94 0 0 1-24 24h-32a23.94 23.94 0 0 1-24-24V24a23.94 23.94 0 0 1 24-24z" class="fa-secondary"/><path d="M504 256c0 136.8-110.8 247.7-247.5 248C120 504.3 8.2 393 8 256.4A248 248 0 0 1 111.8 54.2a24.07 24.07 0 0 1 35 7.7L162.6 90a24 24 0 0 1-6.6 31 168 168 0 0 0 100 303c91.6 0 168.6-74.2 168-169.1a168.07 168.07 0 0 0-68.1-134 23.86 23.86 0 0 1-6.5-30.9l15.8-28.1a24 24 0 0 1 34.8-7.8A247.51 247.51 0 0 1 504 256z" class="fa-primary"/></svg>
            <h1 class="text-uppercase"><?= $title ?></h1>
            <p><?= $info ?></p>
            <button type="button" class="btn btn-primary mt-3">
                <svg id="reload" class="me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!-- Font Awesome Pro 5.15.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M492 8h-10c-6.627 0-12 5.373-12 12v110.625C426.804 57.047 346.761 7.715 255.207 8.001 118.82 8.428 7.787 120.009 8 256.396 8.214 393.181 119.166 504 256 504c63.926 0 122.202-24.187 166.178-63.908 5.113-4.618 5.354-12.561.482-17.433l-7.069-7.069c-4.503-4.503-11.749-4.714-16.482-.454C361.218 449.238 311.065 470 256 470c-117.744 0-214-95.331-214-214 0-117.744 95.331-214 214-214 82.862 0 154.737 47.077 190.289 116H332c-6.627 0-12 5.373-12 12v10c0 6.627 5.373 12 12 12h160c6.627 0 12-5.373 12-12V20c0-6.627-5.373-12-12-12z"/></svg>
				<?= $button ?>
            </button>
        </div>
    </div>
</div>

<script>
    document.querySelector("button").addEventListener("click", () => {
        window.location.reload();
    });
</script>

</body>

</html>