# Procédure complète — Refonte Laravel + Breeze + Cartographie (zones)

## 1) Objectif
Passer d’une app front locale (JSON + localStorage) à une application web hébergée, robuste, avec:
- base de données (MySQL/MariaDB),
- API sécurisée,
- authentification (Breeze),
- cartographie plus propre,
- zones de livraison éditables et persistées.

---

## 2) Choisir la bonne architecture (3 approches)

## Option A — Laravel monolithique (Breeze Blade)
**Principe:** Laravel rend les pages + JS map intégré dans les vues Blade.

**Avantages:**
- simple à déployer,
- rapide à démarrer,
- peu de complexité front.

**Inconvénients:**
- UI dynamique plus limitée qu’une SPA,
- architecture moins découplée.

**Recommandé si:** tu veux livrer vite un outil interne stable.

## Option B — Laravel + Breeze Inertia (Vue ou React)
**Principe:** backend Laravel + front Inertia (sans API séparée complète au début).

**Avantages:**
- expérience front moderne,
- auth déjà bien intégrée,
- progression fluide vers une app plus riche.

**Inconvénients:**
- plus de complexité qu’un Blade pur,
- nécessite confort JS.

**Recommandé si:** tu veux une UX moderne sans partir sur une API full séparée tout de suite.

## Option C — Laravel API + front séparé (Vite/React/Vue)
**Principe:** Laravel sert API uniquement, front indépendant.

**Avantages:**
- séparation claire,
- scalable,
- idéal long terme.

**Inconvénients:**
- plus long à mettre en place,
- auth/cors/token à bien cadrer.

**Recommandé si:** tu vises une plateforme plus grande multi-clients/équipe.

---

## 3) Recommandation concrète
Pour ton contexte actuel: **Option B (Laravel + Breeze Inertia Vue)**.

Pourquoi:
- tu gardes la vitesse de mise en place,
- tu obtiens un front moderne pour la map,
- tu peux évoluer ensuite vers API séparée sans réécriture complète.

---

## 4) Prérequis environnement (Windows / Laragon)

- PHP 8.2+ (idéal 8.3 ou 8.4)
- Composer 2.x
- Node.js 20+
- npm 10+
- MySQL ou MariaDB
- Laragon (ou équivalent)
- Git

Vérifications:
```bash
php -v
composer -V
node -v
npm -v
```

---

## 5) Initialisation projet Laravel

## 5.1 Créer le projet
```bash
composer create-project laravel/laravel Baudry-livraison-map
cd Baudry-livraison-map
```

## 5.2 Configurer `.env`
Mettre:
- `APP_NAME`,
- `APP_URL`,
- `DB_CONNECTION=mysql`,
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

## 5.3 Générer la clé
```bash
php artisan key:generate
```

## 5.4 Tester connexion DB
```bash
php artisan migrate
```

---

## 6) Installer Laravel Breeze (avec options)

## 6.1 Installer package Breeze
```bash
composer require laravel/breeze --dev
```

## 6.2 Choisir ton stack Breeze

### Breeze Blade (simple)
```bash
php artisan breeze:install blade
npm install
npm run build
php artisan migrate
```

### Breeze Inertia + Vue (recommandé)
```bash
php artisan breeze:install vue
npm install
npm run build
php artisan migrate
```

### Breeze Inertia + React
```bash
php artisan breeze:install react
npm install
npm run build
php artisan migrate
```

## 6.3 Variante API auth (Sanctum)
Si front séparé ensuite:
```bash
php artisan breeze:install api
php artisan migrate
```

---

## 7) Modèle de données recommandé

## 7.1 Tables principales

### `cities`
- id
- name (ville)
- postal_code
- lat
- lng
- is_active
- created_at / updated_at

### `agencies`
- id
- name
- lat
- lng
- is_active
- created_at / updated_at

### `delivery_zones`
- id
- agency_id
- name
- min_radius_m
- max_radius_m
- color_hex
- order_index
- is_active
- created_at / updated_at

### `city_coordinate_corrections`
- id
- city_id
- old_lat
- old_lng
- new_lat
- new_lng
- updated_by (user_id)
- reason (nullable)
- created_at

### `users` (Breeze)
- ajouter rôle (admin, dispatcher, viewer)

## 7.2 Index utiles
- `cities(postal_code, name)`
- `delivery_zones(agency_id, order_index)`
- `city_coordinate_corrections(city_id, created_at)`

---

## 8) API recommandée (versionnée)

Prefix: `/api/v1`

- `GET /agencies`
- `GET /agencies/{id}/zones`
- `GET /cities?search=&postal_code=`
- `GET /cities/{id}`
- `PATCH /cities/{id}/coordinates` (auth + rôle)
- `GET /cities/{id}/corrections` (admin)

**Bonnes pratiques:**
- FormRequest pour validation,
- Resources pour format JSON,
- Policies/Gates pour droits.

---

## 9) Cartographie — outils possibles

## Option Map 1 — Leaflet + tuiles Carto/Esri (recommandé MVP)
**Avantages:**
- open-source,
- simple,
- léger.

**Stack:**
- `leaflet`
- éventuellement `leaflet-geoman` pour édition.

## Option Map 2 — MapLibre GL
**Avantages:**
- rendu vectoriel moderne,
- style plus premium,
- meilleures transitions.

**Inconvénients:**
- un peu plus complexe,
- gestion des sources/style plus technique.

## Option Map 3 — Google Maps JS API
**Avantages:**
- écosystème complet,
- géocodage intégré.

**Inconvénients:**
- coût potentiel,
- dépendance fournisseur.

### Reco pratique
- **Phase 1:** Leaflet + tuiles propres.
- **Phase 2:** si besoin premium/perf, migrer vers MapLibre.

---

## 10) Dessin des zones — méthodes possibles

## Méthode A — Cercles concentriques (`L.circle`)
**Usage:** zones radialement simples autour agence.

**Avantages:**
- précis en mètres,
- rapide à implémenter,
- parfait pour ton cas actuel.

**Inconvénients:**
- pas de forme personnalisée.

## Méthode B — Anneaux simulés (polygones)
**Usage:** reproduit ton approche actuelle.

**Avantages:**
- contrôle visuel.

**Inconvénients:**
- plus complexe,
- précision plus fragile.

## Méthode C — Polygones libres (draw/edit)
**Usage:** zones métier non circulaires.

**Avantages:**
- ultra flexible.

**Inconvénients:**
- UX et stockage plus lourds (GeoJSON),
- besoin d’outils d’édition.

### Reco pratique
Commencer avec **Méthode A** puis passer à C seulement si besoin métier réel.

---

## 11) Intégration map côté Laravel (exemple de stratégie)

1. Créer page `MapDashboard` protégée (`auth`).
2. Charger agences + zones depuis API.
3. Charger villes via recherche API (pas de JSON statique).
4. Sur “GPS Update”, envoyer `PATCH /cities/{id}/coordinates`.
5. Sauvegarder correction dans table historique.
6. Recalculer affichage zones en front.

---

## 12) Sécurité & gouvernance

- Auth obligatoire pour toute modif GPS.
- Rôle `admin/dispatcher` pour patch coordonnées.
- Journaliser qui modifie quoi (historique corrections).
- Rate limit API (`throttle`).
- Validation stricte des coordonnées (lat/lng bornées).
- CSRF/session (monolithe) ou Sanctum tokens (SPA/API).

---

## 13) Plan de développement recommandé (progressif)

## Phase 0 — Cadrage (0.5 à 1 jour)
- Choisir Option B (ou autre validée).
- figer schéma DB minimal.

## Phase 1 — Setup socle (1 jour)
- Laravel + Breeze + DB + migrations.
- seed agences de base.

## Phase 2 — Lecture données (1 à 2 jours)
- Endpoints `agencies`, `zones`, `cities`.
- map affichage agences + zones.

## Phase 3 — Update GPS sécurisé (1 jour)
- endpoint PATCH coords,
- historique corrections,
- contrôle rôle.

## Phase 4 — UX map (1 à 2 jours)
- meilleur fond de carte,
- `L.circle` concentriques,
- édition basique si nécessaire.

## Phase 5 — QA & déploiement (1 jour)
- tests manuels,
- sauvegardes DB,
- monitoring erreurs.

---

## 14) Déploiement hébergeur (checklist)

- Créer DB prod + utilisateur dédié.
- Déployer code (`git pull` ou pipeline).
- `composer install --no-dev --optimize-autoloader`
- `php artisan migrate --force`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `npm run build` (en CI idéalement)
- pointer document root sur `public/`
- mettre `APP_ENV=production`, `APP_DEBUG=false`

---

## 15) Stratégie de migration depuis l’app actuelle

1. Importer `villes.json` vers table `cities`.
2. Vérifier doublons ville/code.
3. Migrer agences/zones actuelles.
4. Basculer front vers API lecture.
5. Basculer GPS Update vers API patch.
6. Supprimer dépendance `localStorage`/JSON comme source de vérité.

---

## 16) Commandes utiles (dev)

```bash
php artisan serve
npm run dev
php artisan migrate:fresh --seed
php artisan test
```

---

## 17) Décision rapide (si tu veux aller vite)

- Architecture: **Laravel + Breeze Inertia Vue**
- Map: **Leaflet + Carto/Esri**
- Zones: **cercles concentriques `L.circle`**
- Auth: **Breeze session**, puis Sanctum si front séparé
- Source de vérité: **MySQL uniquement**

---

## 18) Étape suivante recommandée
Valider ces 5 choix:
1. Breeze `blade` ou `vue`
2. Leaflet ou MapLibre
3. Cercles simples ou polygones
4. Rôles utilisateurs nécessaires
5. Hébergeur exact (mutualisé/VPS) pour ajuster le déploiement

Une fois validé, tu peux lancer l’implémentation sans ambiguïté.

