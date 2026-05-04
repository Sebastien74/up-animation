# CLAUDE.md

## Project Overview
Projet Symfony 7.4 utilisant PHP 8.5 Il s'agit d'une application web avec Symfony Framework, Doctrine ORM et Webpack Encore pour la gestion des assets.

## Commands
### Backend (Symfony)
- Install: `composer install`
- Database: `php bin/console doctrine:database:create` / `php bin/console doctrine:migrations:migrate`
- Fixtures: `php bin/console doctrine:fixtures:load`
- Cache Clear: `php bin/console cache:clear`
- Routing JS: `php bin/console fos:js-routing:dump`

### Frontend (Yarn)
- Install: `yarn install`
- Build: `yarn build`
- Watch: `yarn watch`
- Dev Server: `yarn dev-server`

## Architecture
- `src/`: Code source PHP (Controllers, Entity, Service, Form, Command, etc.)
- `templates/`: Templates Twig
- `assets/`: Sources frontend (SCSS, JS, images)
- `public/`: Fichiers publics et assets compilés
- `config/`: Configuration de l'application
- `migrations/`: Migrations de base de données

## Code Style
- **PHP**: PSR-12, utiliser le type-hinting strict (PHP 8.3). Injection de dépendances via le constructeur.
- **Twig**: Utiliser le formatage standard Symfony.
- **Frontend**: SCSS pour les styles, Stimulus pour le JS comportemental.

## Development Notes
- Utiliser `symfony console` si le binaire Symfony est disponible, sinon `php bin/console`.
- L'application utilise `Webpack Encore` pour compiler les assets.
- Les fichiers d'environnement sont gérés via `.env` et `.env.local`.

## Figma Integration
Si une demande d'implémentation de design à partir de Figma est reçue (ex: `@https://www.figma.com/design/...`), respecter les emplacements suivants :
- **HTML (Twig)**: `templates/front/template/figma.html.twig`
- **CSS (SCSS)**: `assets/scss/front/default/templates/figma.scss`
- **JS**: `assets/js/front/default/templates/figma.js`

**Consignes supplémentaires :**
- **Images**: Utiliser systématiquement `asset('medias/placeholder.jpg')` pour toutes les images.
- **Framework CSS**: Utiliser au maximum les classes **Bootstrap** (grilles, marges, padding, etc.) pour limiter le CSS personnalisé.
- **Class**: Ne pas mettre le mot figma dans les classes CSS is id.
