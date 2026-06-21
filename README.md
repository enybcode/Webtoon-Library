# WebtoonLib — Documentation projet BTS SIO SLAM

---

## Site en ligne

Le site est accessible ici : [https://webtoon.mangawatch.org](https://webtoon.mangawatch.org)

---

## Structure des fichiers

```
webtoon-app/
│
├── index.php                  → Page d'accueil publique
├── connexion.php              → Formulaire de connexion
├── inscription.php            → Formulaire d'inscription
├── dashboard.php              → Espace personnel (protégé)
├── webtoons.php               → Liste complète (protégée)
├── ajouter_webtoon.php        → Formulaire d'ajout (protégé)
├── modifier_webtoon.php       → Formulaire de modification (protégé)
├── supprimer_webtoon.php      → Suppression (protégé, pas de vue)
├── logout.php                 → Déconnexion
│
├── assets/
│   ├── css/style.css          → Toute la mise en forme du site
│   └── js/script.js           → Filtres, confirmation suppression, aperçu image
│
├── includes/
│   ├── config.php             → Connexion PDO à MySQL
│   ├── header.php             → En-tête HTML + navbar (inclus sur toutes les pages)
│   └── footer.php             → Pied de page HTML (inclus sur toutes les pages)
│
└── database/
    └── database.sql           → Script SQL pour créer la BDD
```

---

## Installation (XAMPP / WAMP)

1. Copier le dossier `webtoon-app` dans `htdocs/` (XAMPP) ou `www/` (WAMP)
2. Ouvrir phpMyAdmin → Créer une base `webtoon_app`
3. Importer le fichier `database/database.sql`
4. Vérifier `includes/config.php` : adapter `DB_USER` et `DB_PASS` si besoin
5. Ouvrir `http://localhost/webtoon-app/`

---

## Fonctionnement général

### Système de sessions PHP
Quand un utilisateur se connecte avec succès :
- `$_SESSION['user_id']` est défini avec son ID en base
- `$_SESSION['user_pseudo']` contient son pseudo

Toutes les pages "protégées" commencent par :
```php
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}
```
Ce bloc redirige vers la connexion si l'utilisateur n'est pas connecté.

### Sécurité des données
- Les mots de passe sont hashés avec `password_hash()` et vérifiés avec `password_verify()`
- Toutes les requêtes SQL utilisent des **requêtes préparées PDO** (`prepare` + `execute`) pour éviter les injections SQL
- Les données affichées en HTML passent par `htmlspecialchars()` pour éviter les failles XSS
- Chaque action sur un webtoon vérifie que `id_utilisateur = $_SESSION['user_id']`, empêchant tout accès aux données d'un autre utilisateur

---

## Compétences BTS SIO SLAM utilisées

| Compétence | Mise en œuvre |
|---|---|
| Développement web côté serveur | PHP procédural avec sessions, PDO, password_hash |
| Développement web côté client | HTML5, CSS3, JavaScript (DOM, événements) |
| Conception BDD | Modèle relationnel : 2 tables liées par clé étrangère |
| SQL | CREATE TABLE, INSERT, SELECT, UPDATE, DELETE, FOREIGN KEY |
| Sécurité applicative | Requêtes préparées, hash mot de passe, contrôle d'accès par session |
| CRUD complet | Create (ajouter), Read (lister), Update (modifier), Delete (supprimer) |
| Séparation des responsabilités | Includes (config, header, footer) réutilisables |
| UX / Interface utilisateur | Responsive CSS, filtres JS, messages d'erreur/succès |

---

## Compte de test
- **Pseudo** : TestUser
- **Mot de passe** : password123
