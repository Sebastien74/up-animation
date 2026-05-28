# Up Animation

[![Generic badge](https://img.shields.io/badge/Version-7-purple.svg?style=flat-square&color=rgba(120,5,120))](https://github.com/Sebastien74/SFCMS-7)
![Generic badge](https://img.shields.io/badge/PHP-8.5-red.svg?style=flat-square)
![Generic badge](https://img.shields.io/badge/Node-v.20-green.svg?style=flat-square&color=rgba(29,153,91,.7))
[![Generic badge](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](https://github.com/Sebastien74/MIT-LICENSE/blob/main/LICENSE.md)
[![Generic badge](https://img.shields.io/badge/Author-Sébastien%20FOURNIER-blue.svg?style=flat-square)](https://github.com/Sebastien74)
[![Generic badge](https://img.shields.io/badge/Contributor-1-blue.svg?style=flat-square)](https://github.com/Sebastien74)
---

#### Prod: 
#### Prod serveur:

#### Preprod:
#### Preprod serveur:

#### Bundles Packagist: https://packagist.org/users/seybi74/packages

---

### Installation

#### 1. Configuration des fichiers

> Créer `.env.local`, `.env.preprod` et `.env.prod` à la racine

> Copier le contenu de `.env.dist` dans ces fichiers et compléter la configuration

> Compléter `./bin/data/config/default.yaml`

> Remplacer les médias par défaut dans `./assets/medias/images/default`

> Adapter les variables SCSS dans `./assets/scss/front/default/variables.scss`

#### 2. Initialisation

```bash
composer install
php composer.phar update --with-all-dependencies
php composer.phar dump-autoload

php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load --no-interaction

yarn install
yarn build
```

---

### Commandes

#### Backend (Symfony)

```bash
composer install                                                            # dépendances PHP
php bin/console doctrine:database:create                                    # création base
php bin/console doctrine:migrations:migrate                                 # migrations
php bin/console doctrine:schema:update --force                              # mise à jour schéma
php bin/console doctrine:fixtures:load --no-interaction                     # fixtures
php bin/console cache:clear                                                 # cache Symfony
php bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json
php bin/console assets:install                                              # assets statiques
```

#### Frontend (Yarn)

```bash
yarn install
yarn dev-server                                                             # dev server avec HMR
yarn watch                                                                  # build dev en watch
yarn dev                                                                    # build dev unique
yarn build                                                                  # build production optimisé
yarn build:font-fallbacks                                                   # génération fallbacks polices
yarn xss-check                                                              # audit patterns XSS
```

Mise à jour des dépendances :

```bash
yarn upgrade-interactive --latest                                           # upgrade ciblé
yarn upgrade --latest                                                       # upgrade global
npx update-browserslist-db@latest                                           # update browserslist
```

#### Tests

```bash
php bin/phpunit                                                             # suite complète
php bin/phpunit --display-deprecations                                      # avec deprecations
php bin/phpunit --testdox                                                   # sortie lisible par cas
php bin/phpunit --filter NewsletterEmailTest                                # test ciblé
php bin/phpunit tests/Form/Manager/Front/                                   # dossier ciblé
```

Tests d'envoi de mail disponibles dans `tests/` (Newsletter, Contact, Inscription, Reset Password front/back, 2FA, Password Expire). Utilisent un transport mailer `null://null` et la `MailerAssertionsTrait` Symfony — aucune base de données requise.

#### Commandes custom

Crawl et import SEO :

```bash
php bin/console app:crawl:all                                               # pipeline complet
php bin/console app:crawl:internal-urls                                     # découverte URLs
php bin/console app:crawl:contents-map                                      # mapping contenus
php bin/console app:crawl:metas                                             # extraction métadonnées
php bin/console app:crawl:product-contents                                  # enrichissement produits
php bin/console app:crawl:category-urls                                     # crawl catégories
php bin/console app:crawl:pages-urls                                        # crawl pages
php bin/console app:import:contents                                         # import JSON en DB
```

Cache et assets :

```bash
php bin/console app:cache:clear                                             # clear optimisé (rename FS)
php bin/console liip:imagine:cache:remove                                   # clear thumbnails Liip
php bin/console app:cache:unused                                            # purge fichiers cache inutilisés
```

Médias :

```bash
php bin/console app:thumbs:generate                                         # regénération thumbnails
php bin/console app:media:update-info                                       # métadonnées (dimensions, EXIF)
```

Scheduler et analytics :

```bash
php bin/console scheduler:execute                                           # exécute les commandes planifiées
php bin/console app:analytics:install-scheduler                             # installation rollup/purge
php bin/console app:analytics:rollup                                        # agrégation horaire/journalière
php bin/console app:analytics:purge                                         # suppression événements anciens
php bin/console app:analytics:seed-fake                                     # données analytics de test
```

Sécurité et GDPR :

```bash
php bin/console security:reset:token                                        # invalide tokens reset password > 24h
php bin/console security:password:expire                                    # marque mots de passe expirés
php bin/console gdpr:remove                                                 # suppression données utilisateur
php bin/console app:contact:delete                                          # purge anciens messages contact
```

Traductions :

```bash
php bin/console app:cmd:translations                                        # extraction + génération traductions
```

#### Outils dev

```bash
./bin/mailpit.exe                                                           # interface SMTP catcher local
php php-cs-fixer.phar fix src/                                              # PHP CS Fixer
```

### Production
#### Optimize Composer Autoloader
```bash
php composer.phar dump-autoload --no-dev --classmap-authoritative --optimize
php composer.phar dump-env prod
```
### Git

#### To generate an archive by commit number:
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

#### To load a large SQL file
```bash
Get-Content "C:\Users\fourn\Downloads\filename.sql" -Raw | & "C:\wamp64\bin\mysql\mysql5.7.44\bin\mysql.exe" -u root -p -h 127.0.0.1 -P 3306 db_ame
```

#### To change the length limit of characters to search word with fulltext
```bash
# To mysql my.ini set this variable:
innodb_ft_max_token_size: 100
```

#### Deletion of a too large git history file after commit and push doesn't work:
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
#### To switch Node.js version: run PowerShell as administrator
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