# Déploiement sur Hostinger (Laravel + MySQL)

Ce guide sert à mettre en ligne cette application Laravel sur Hostinger avec une base MySQL.

---

## 1) Prérequis

- Un hébergement Hostinger avec PHP 8.2+ (idéal: 8.3/8.4)
- Une base MySQL créée dans hPanel
- Accès au gestionnaire de fichiers Hostinger (ou FTP)
- Optionnel mais recommandé: accès SSH

---

## 2) Préparer le projet en local

Depuis le projet:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Si `npm ci` échoue sous Windows avec une erreur `EPERM` sur `esbuild.exe`, utiliser:

```bash
npm install
npm run build
```

Puis:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Important: ne jamais uploader `.env` local en production.

---

## 3) Créer la base MySQL sur Hostinger

Dans hPanel:

1. Aller dans **Databases > MySQL Databases**
2. Créer une base, un user, et un mot de passe
3. Noter:
   - `DB_HOST`
   - `DB_PORT` (souvent `3306`)
   - `DB_DATABASE`
   - `DB_USERNAME`
   - `DB_PASSWORD`

---

## 4) Structure de déploiement (recommandée)

Le plus propre est:

- code Laravel (app, bootstrap, config, vendor, etc.) **hors public**
- dossier `public/` exposé web

### Cas A — tu peux définir le Document Root vers `/public`

- Uploader le projet complet
- Pointer le domaine/subdomaine vers `.../public`

### Cas B — tu dois utiliser `public_html` (shared classique)

1. Mettre le projet Laravel dans un dossier privé (ex: `~/baudry-map`)
2. Copier le contenu de `baudry-map/public/` dans `public_html/`
3. Éditer `public_html/index.php` pour corriger les chemins:

```php
require __DIR__.'/../baudry-map/vendor/autoload.php';
$app = require_once __DIR__.'/../baudry-map/bootstrap/app.php';
```

Adapte `baudry-map` selon ton vrai dossier si besoin.

---

## 5) Configurer `.env` en production

Créer le fichier `.env` sur le serveur (à la racine Laravel):

```env
APP_NAME="Baudry Livraison Map"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://ton-domaine.tld

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xxxx
DB_USERNAME=xxxx
DB_PASSWORD=xxxx

LOG_CHANNEL=stack
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Ensuite générer la clé:

```bash
php artisan key:generate --force
```

---

## 6) Installer dépendances serveur

### Si SSH disponible

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Si SSH non disponible

- Faire `composer install --no-dev` en local
- Uploader aussi le dossier `vendor/`
- Uploader `public/build/` (généré par `npm run build`)
- Lancer les migrations depuis terminal Hostinger si dispo, sinon via script temporaire sécurisé (à retirer ensuite)

---

## 7) Permissions à vérifier

Laravel doit pouvoir écrire dans:

- `storage/`
- `bootstrap/cache/`

Sur Hostinger shared, c’est souvent déjà OK. Sinon corriger via File Manager/SSH.

---

## 8) Checklist de mise en ligne

- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` correct (https)
- [ ] `.env` serveur correct (DB + app)
- [ ] migrations passées
- [ ] assets présents: `public/build/*`
- [ ] `storage` et `bootstrap/cache` accessibles en écriture
- [ ] login OK
- [ ] page carte OK
- [ ] page admin OK

---

## 9) Commandes utiles en maintenance

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 10) Dépannage Windows (erreur npm EPERM sur esbuild)

Symptôme typique:
- `EPERM: operation not permitted, unlink ... esbuild.exe`

Cause:
- fichier verrouillé (Vite/Node encore actif), antivirus, ou droits insuffisants.

Étapes:

```bash
# 1) Fermer les serveurs/dev watchers (Vite, artisan serve)

# 2) En PowerShell Admin (si besoin), tuer les processus restants
taskkill /F /IM node.exe
taskkill /F /IM esbuild.exe

# 3) Nettoyer les dépendances
rmdir /s /q node_modules
del package-lock.json

# 4) Réinstaller puis build
npm install
npm run build
```

Si l'antivirus bloque encore, ajoute une exclusion temporaire sur le dossier du projet le temps de l'installation.

---

## 11) Déploiement rapide (résumé)

1. Build local (`composer --no-dev`, `npm run build`)
2. Uploader code + `vendor` + `public/build`
3. Configurer `.env` prod
4. Générer `APP_KEY`
5. Lancer migrations
6. Cacher config/routes/views

---

## 12) Sécurité minimale recommandée

- Garder `.env` hors `public_html`
- Désactiver debug (`APP_DEBUG=false`)
- Utiliser HTTPS forcé
- Ne jamais exposer les clés/API dans le repo public
- Supprimer tout script temporaire de migration/import
