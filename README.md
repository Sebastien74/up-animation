![Up Animation](docs/logo-readme.png)

# Up Animation

[![Version](https://img.shields.io/badge/Version-7-7805C8.svg?style=for-the-badge)](https://github.com/Sebastien74/SFCMS-7)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Node](https://img.shields.io/badge/Node-v20-339933.svg?style=for-the-badge&logo=node.js&logoColor=white)](https://nodejs.org/)
[![License](https://img.shields.io/badge/License-MIT-1E88E5.svg?style=for-the-badge)](https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
[![Author](https://img.shields.io/badge/Author-S%C3%A9bastien%20FOURNIER-1E88E5.svg?style=for-the-badge)](https://github.com/Sebastien74)
[![Contributors](https://img.shields.io/badge/Contributors-1-1E88E5.svg?style=for-the-badge)](https://github.com/Sebastien74)

---

## Environments

| Environment       | Application | Server |
| ----------------- | ----------- | ------ |
| **Production**    | _to define_ | _to define_ |
| **Pre-production**| _to define_ | _to define_ |

> **Packagist bundles** — https://packagist.org/users/seybi74/packages

---

## Contents

1. [Getting started](#getting-started)
2. [Production](#production)
3. [Git](#git)
4. [MySQL](#mysql)
5. [Packagist](#packagist)
6. [PHP CS Fixer](#php-cs-fixer)
7. [o2Switch](#o2switch)
8. [Node.js](#nodejs)
9. [pagespeed_module](#pagespeed_module)

---

## Getting started

### Step 1 — Files configuration

> - Create `.env.local`, `.env.preprod` & `.env.prod` files in the root directory.
> - Copy and paste `.env.dist` content into the `.env` files and complete the configuration.
> - Complete the `./bin/data/config/default.yaml` configuration file.
> - Replace default medias in `./assets/medias/images/default`.
> - Adjust SCSS variables in `./assets/scss/front/default/variables.scss`.

### Step 2 — Bootstrap commands

**Composer & Doctrine**

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

**Assets**

```bash
php bin/console assets:install
php bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json
```

**Yarn**

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

---

## Production

**Optimize Composer Autoloader**

```bash
php composer.phar dump-autoload --no-dev --classmap-authoritative --optimize
php composer.phar dump-env prod
```

---

## Git

**Generate an archive by commit number**

```bash
git archive --output=changes.zip HEAD $(git diff --name-only 0000000..HEAD --diff-filter=ACMRTUXB)
```

**Upgrade `.gitignore` file**

```bash
git rm -r --cached .
git add .
git commit -m ".gitignore update"
```

---

## MySQL

**Load a large SQL file**

```bash
Get-Content "C:\Users\fourn\Downloads\filename.sql" -Raw | & "C:\wamp64\bin\mysql\mysql5.7.44\bin\mysql.exe" -u root -p -h 127.0.0.1 -P 3306 db_ame
```

**Change the character length limit for fulltext search**

```bash
# To mysql my.ini set this variable:
innodb_ft_max_token_size: 100
```

**Delete a too-large git history file after commit and push doesn't work**

```bash
git filter-branch --index-filter "git rm -rf --cached --ignore-unmatch assets/medias/images/front/default/video.m4v" HEAD
git update-ref -d refs/original/refs/heads/master
```

---

## Packagist

**Tag management**

```bash
# To add tag
git tag v1.0.0
git push --tags -u origin <branchname>

# To remove all tags
git tag | foreach-object -process { git push origin --delete $_ }
git tag | foreach-object -process { git tag -d $_ }
```

---

## PHP CS Fixer

**Fix `/src` repository**

```bash
# To fix /src repository
php php-cs-fixer.phar fix src/

# To remove all tags
git tag | foreach-object -process { git push origin --delete $_ }
git tag | foreach-object -process { git tag -d $_ }
```

---

## o2Switch

**Cron & SPF**

```bash
# Cron by URL
wget -O /dev/null "URL"
# To send email correctly use this spf value
v=spf1 a mx include:spf.jabatus.fr ~all
```

---

## Node.js

> To switch Node.js version, run PowerShell as administrator — see [nvm-windows](https://github.com/coreybutler/nvm-windows).

**Main commands** _(PowerShell as Administrator)_

```bash
# Mains commands - Run this commands in PowerShell as Administrator
nvm list
nvm install **.**.* # (npm install --global yarn)
nvm use **.**.*
```

---

## pagespeed_module

**Apache configuration**

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
