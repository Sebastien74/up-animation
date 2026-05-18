<div align="center">

<img src="docs/logo-readme.png" alt="Up Animation" height="72">

# Up Animation

[![Version](https://img.shields.io/badge/Version-7-purple.svg?style=for-the-badge&color=7805C8)](https://github.com/Sebastien74/SFCMS-7)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Node](https://img.shields.io/badge/Node-v20-339933.svg?style=for-the-badge&logo=node.js&logoColor=white)](https://nodejs.org/)
[![License](https://img.shields.io/badge/License-MIT-1E88E5.svg?style=for-the-badge)](https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
[![Author](https://img.shields.io/badge/Author-Sébastien%20FOURNIER-1E88E5.svg?style=for-the-badge)](https://github.com/Sebastien74)
[![Contributors](https://img.shields.io/badge/Contributors-1-1E88E5.svg?style=for-the-badge)](https://github.com/Sebastien74)

</div>

---

## Environments

| Environment       | Application | Server |
| ----------------- | ----------- | ------ |
| **Production**    |             |        |
| **Pre-production**|             |        |

> **Packagist bundles** — https://packagist.org/users/seybi74/packages

---

## Table of contents

- [Getting started](#getting-started)
- [Production](#production)
- [Cheat sheets](#cheat-sheets)
  - [Git](#git)
  - [MySQL](#mysql)
  - [Packagist](#packagist)
  - [PHP CS Fixer](#php-cs-fixer)
  - [o2Switch](#o2switch)
  - [Node.js](#nodejs)
  - [pagespeed_module](#pagespeed_module)

---

## Getting started

### 1. Files configuration

> Create `.env.local`, `.env.preprod` & `.env.prod` files in the root directory.
>
> Copy and paste `.env.dist` content into the `.env` files and complete the configuration.
>
> Complete the `./bin/data/config/default.yaml` configuration file.
>
> Replace default medias in `./assets/medias/images/default`.
>
> Adjust SCSS variables in `./assets/scss/front/default/variables.scss`.

### 2. Bootstrap commands

<details open>
<summary><b>Composer & Doctrine</b></summary>

```bash
# Composer dev mode
php composer.phar dump-autoload

# Doctrine
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load --no-interaction

# Extras :

php bin/phpunit --display-deprecations
```

</details>

<details>
<summary><b>Assets</b></summary>

```bash
php bin/console assets:install
php bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json
```

</details>

<details>
<summary><b>Yarn</b></summary>

```bash
yarn cache clean
yarn install
yarn dev --watch # Dev mode
yarn encore production # Production mode

# Extras :

# To check if dependencies are up to date / To upgrade dependencies remove yarn.lock and reinstall all node_modules
yarn upgrade-interactive --latest

# To upgrade all dependencies in same time
yarn yarn-upgrade-all

# To update yarn to last version
npm install --global yarn

# To update all dependencies to latest
yarn upgrade --latest

# To update browserslist
npx update-browserslist-db@latest
```

</details>

---

## Production

**Optimize Composer Autoloader**

```bash
php composer.phar dump-autoload --no-dev --classmap-authoritative --optimize
php composer.phar dump-env prod
```

---

## Cheat sheets

### Git

<details>
<summary><b>Generate an archive by commit number</b></summary>

```bash
git archive --output=changes.zip HEAD $(git diff --name-only 0000000..HEAD --diff-filter=ACMRTUXB)
```

</details>

<details>
<summary><b>Upgrade .gitignore file</b></summary>

```bash
git rm -r --cached .
git add .
git commit -m ".gitignore update"
```

</details>

### MySQL

<details>
<summary><b>Load a large SQL file</b></summary>

```bash
Get-Content "C:\Users\fourn\Downloads\filename.sql" -Raw | & "C:\wamp64\bin\mysql\mysql5.7.44\bin\mysql.exe" -u root -p -h 127.0.0.1 -P 3306 db_ame
```

</details>

<details>
<summary><b>Change character length limit for fulltext search</b></summary>

```bash
# To mysql my.ini set this variable:
innodb_ft_max_token_size: 100
```

</details>

<details>
<summary><b>Delete a too-large git history file after commit and push doesn't work</b></summary>

```bash
git filter-branch --index-filter "git rm -rf --cached --ignore-unmatch assets/medias/images/front/default/video.m4v" HEAD
git update-ref -d refs/original/refs/heads/master
```

</details>

### Packagist

<details>
<summary><b>Tag management</b></summary>

```bash
# To add tag
git tag v1.0.0
git push --tags -u origin <branchname>

# To remove all tags
git tag | foreach-object -process { git push origin --delete $_ }
git tag | foreach-object -process { git tag -d $_ }
```

</details>

### PHP CS Fixer

<details>
<summary><b>Fix /src repository</b></summary>

```bash
# To fix /src repository
php php-cs-fixer.phar fix src/

# To remove all tags
git tag | foreach-object -process { git push origin --delete $_ }
git tag | foreach-object -process { git tag -d $_ }
```

</details>

### o2Switch

<details>
<summary><b>Cron & SPF</b></summary>

```bash
# Cron by URL
wget -O /dev/null "URL"
# To send email correctly use this spf value
v=spf1 a mx include:spf.jabatus.fr ~all
```

</details>

### Node.js

To switch Node.js version, run PowerShell as administrator — see [nvm-windows](https://github.com/coreybutler/nvm-windows).

<details>
<summary><b>Main commands (PowerShell as Administrator)</b></summary>

```bash
# Mains commands - Run this commands in PowerShell as Administrator
nvm list
nvm install **.**.* # (npm install --global yarn)
nvm use **.**.*
```

</details>

### pagespeed_module

<details>
<summary><b>Apache configuration</b></summary>

```apacheconf
# o2switch pagespeed start / DO NOT REMOVE OR EDIT
<IfModule pagespeed_module>
    ModPagespeed on
    ModPagespeedRewriteLevel PassThrough
    ModPagespeedEnableFilters add_head,canonicalize_javascript_libraries,collapse_whitespace,combine_css,combine_javascript,combine_heads,convert_meta_tags,dedup_inlined_images,defer_javascript,elide_attributes,extend_cache,recompress_images,flatten_css_imports,hint_preload_subresources,inline_css,inline_javascript,lazyload_images,rewrite_javascript,move_css_above_scripts,move_css_to_head,insert_dns_prefetch,remove_comments,remove_quotes,rewrite_images,strip_image_meta_data,sprite_images
</IfModule>
# o2switch pagespeed end / DO NOT REMOVE OR EDIT

<IfModule pagespeed_module>
    ModPagespeedDisallow "*/admin-*"
    ModPagespeedDisallow "*/build/fonts*"
</IfModule>
```

</details>
