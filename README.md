# SFCMS 7

[![Generic badge](https://img.shields.io/badge/Version-7-purple.svg?style=flat-square&color=rgba(120,5,120))](https://github.com/Sebastien74/SFCMS-7)
![Generic badge](https://img.shields.io/badge/PHP-8.3-red.svg?style=flat-square)
![Generic badge](https://img.shields.io/badge/Node-v.20-green.svg?style=flat-square&color=rgba(29,153,91,.7))
[![Generic badge](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
[![Generic badge](https://img.shields.io/badge/Author-Sébastien%20FOURNIER-blue.svg?style=flat-square)](https://github.com/Sebastien74)
[![Generic badge](https://img.shields.io/badge/Contributor-1-blue.svg?style=flat-square)](https://github.com/Sebastien74)
---

#### Prod:
#### Prod serveur:

#### Preprod:
#### Preprod serveur:

#### Bundles Packagist : https://packagist.org/users/seybi74/packages

---

### Getting started

#### 1. Files configuration

> Create ```.env.local```, ```.env.preprod``` & ```.env.prod``` file in root dir

> Copy and paste ```.env.dist``` content in ```.env``` files and complete configuration

> Complete ```./bin/data/config/default.yaml``` file configuration

> Change defaults medias in ```./assets/medias/images/default``` dir

> Change scss variables in ```./assets/scss/front/default/variables.scss``` file

#### 2. Run this commands

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

```bash
# Assets
php bin/console assets:install
php bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json
```

```bash
# Yarn
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

### Production
#### Optimize Composer Autoloader
```bash
php composer.phar dump-autoload --no-dev --classmap-authoritative --optimize
php composer.phar dump-env prod
```
### Git

#### To generate archive by commit number:
```bash
git archive --output=changes.zip HEAD $(git diff --name-only 0000000..HEAD --diff-filter=ACMRTUXB)
```

#### To upgrade .gitignore file:
```bash
git rm -r --cached .
git add .
git commit -m ".gitignore update"
```

### MySQL

#### To load large SQL file
```bash
Get-Content "C:\Users\fourn\Downloads\filename.sql" -Raw | & "C:\wamp64\bin\mysql\mysql5.7.44\bin\mysql.exe" -u root -p -h 127.0.0.1 -P 3306 db_ame
```

#### To change length limit of characters to search word with fulltext
```bash
# To mysql my.ini set this variable:
innodb_ft_max_token_size: 100
```

#### Deletion of too large git history file after commit and push doesn't work:
```bash
git filter-branch --index-filter "git rm -rf --cached --ignore-unmatch assets/medias/images/front/default/video.m4v" HEAD
git update-ref -d refs/original/refs/heads/master
```

### Packagist
```bash
# To add tag
git tag v1.0.0
git push --tags -u origin <branchname>

# To remove all tags
git tag | foreach-object -process { git push origin --delete $_ }
git tag | foreach-object -process { git tag -d $_ }
```

### PHP Cs Fixer
```bash
# To fix /src repository
php php-cs-fixer.phar fix src/

# To remove all tags
git tag | foreach-object -process { git push origin --delete $_ }
git tag | foreach-object -process { git tag -d $_ }
```

### o2Swhitch
```bash
# Cron by URL
wget -O /dev/null "URL"
# To send email correctly use this spf value
v=spf1 a mx include:spf.jabatus.fr ~all
```

### NodeJS
#### To switch NodeJS version: run PowerShell as administrator
[https://github.com/coreybutler/nvm-windows](https://github.com/coreybutler/nvm-windows)

```bash
# Mains commands - Run this commands in PowerShell as Administrator
nvm list
nvm install **.**.* # (npm install --global yarn)
nvm use **.**.*
```

### pagespeed_module
```bash
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