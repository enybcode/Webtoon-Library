# Webtoon-Library

Webtoon-Library est une application web realisee en PHP pour gerer une bibliotheque personnelle de webtoons, manhwa et mangas.

Le projet permet a un utilisateur de creer un compte, se connecter, rechercher des oeuvres via l'API AniList, les ajouter a sa liste personnelle, suivre son avancement de lecture et consulter des informations detaillees sur chaque oeuvre.

## Site en ligne

Le site est accessible ici : [https://webtoon.mangawatch.org](https://webtoon.mangawatch.org)

## Objectif du projet

L'objectif est de proposer une application simple et claire, adaptee a un projet BTS SIO SLAM, avec :

- une gestion d'utilisateurs avec sessions PHP ;
- une bibliotheque personnelle par utilisateur ;
- une recherche de webtoons/mangas via AniList ;
- une page de tendances ;
- une page detail pour chaque oeuvre ;
- un suivi personnel de lecture ;
- un espace de parametres ;
- un espace administrateur ;
- des protections de securite de base contre les injections SQL et les actions non autorisees.

## Fonctionnalites principales

- Inscription et connexion utilisateur
- Tableau de bord personnel
- Recherche d'oeuvres avec l'API AniList
- Affichage des tendances AniList
- Ajout d'une oeuvre dans sa bibliotheque
- Page detail avec image, genres, statut, chapitres, score et lien AniList
- Suivi personnel :
  - statut de lecture
  - chapitre actuel
  - note personnelle
  - commentaire
  - intention de continuer ou non
- Gestion de la langue dans les parametres
- Option pour inclure ou non les contenus +18
- Espace admin pour gerer les utilisateurs, les categories et certaines donnees

## Structure du projet

```text
Webtoon-Library/
|-- index.php
|-- connexion.php
|-- inscription.php
|-- dashboard.php
|-- webtoons.php
|-- rechercher.php
|-- tendance.php
|-- detail_webtoon.php
|-- ajouter_webtoon.php
|-- modifier_webtoon.php
|-- supprimer_webtoon.php
|-- parametres.php
|-- admin_db.php
|-- logout.php
|
|-- includes/
|   |-- config.php
|   |-- header.php
|   |-- footer.php
|   |-- lang.php
|   |-- traductions.php
|   |-- security.php
|   |-- deepl_config.php
|
|-- assets/
|   |-- css/
|   |-- img/
|   |-- js/
|
|-- database/
|   |-- database.sql
|   |-- update_suivi.sql
|   |-- update_langues.sql
|   |-- update_francais.sql
```

## Installation locale

1. Copier le dossier du projet dans `htdocs/` avec XAMPP.
2. Creer une base de donnees appelee `webtoon_library`.
3. Importer le fichier `database/database.sql` dans phpMyAdmin.
4. Verifier les identifiants dans `includes/config.php`.
5. Ouvrir le site en local avec une URL du type :

```text
http://localhost/Webtoon-Library/
```

## Fonctionnement general

L'utilisateur doit etre connecte pour acceder aux pages principales de l'application.

Quand il se connecte, le site cree une session PHP avec son identifiant utilisateur. Les pages protegees verifient ensuite la presence de cette session avant d'afficher le contenu.

Les oeuvres ajoutees sont stockees dans la table `webtoons` avec un champ `id_utilisateur`. Cela permet de separer les bibliotheques : chaque utilisateur voit uniquement ses propres oeuvres.

## API AniList

Le projet utilise l'API AniList en GraphQL pour recuperer les donnees des webtoons, manhwa et mangas.

Les donnees recuperees peuvent contenir :

- le titre ;
- l'image de couverture ;
- les genres ;
- la description ;
- le statut ;
- le nombre de chapitres ;
- la note moyenne ;
- la popularite ;
- le lien vers AniList.

L'identifiant AniList est stocke en base avec `anilist_id`, mais il n'est pas affiche directement a l'utilisateur.

## Securite

Le projet integre plusieurs protections importantes :

- mots de passe hashes avec `password_hash()` ;
- verification des mots de passe avec `password_verify()` ;
- requetes SQL preparees avec PDO ;
- protection contre les injections SQL ;
- verification des sessions sur les pages protegees ;
- verification que l'utilisateur modifie uniquement ses propres oeuvres ;
- protection CSRF sur les formulaires sensibles ;
- suppression en POST avec token CSRF ;
- echappement HTML avec `htmlspecialchars()` pour limiter les risques XSS ;
- regeneration de l'identifiant de session apres connexion.

## Base de donnees

Les tables principales sont :

- `utilisateurs` : comptes utilisateurs, langue et role admin ;
- `webtoons` : oeuvres ajoutees et suivi personnel ;
- `traductions_cache` : cache des traductions ;
- `categories_admin` : categories gerables depuis l'espace admin.

## Competences BTS SIO SLAM mobilisees

| Competence | Mise en oeuvre |
|---|---|
| Developpement web cote serveur | PHP, sessions, PDO |
| Developpement web cote client | HTML, CSS, JavaScript |
| Base de donnees | SQL, tables relationnelles, cles etrangere |
| Securite applicative | Hash mot de passe, PDO, CSRF, controle d'acces |
| API externe | Utilisation de l'API AniList GraphQL |
| CRUD | Ajouter, lire, modifier et supprimer des oeuvres |
| Administration | Gestion simple des utilisateurs et categories |

## Conclusion

Webtoon-Library est une application web complete pour gerer une bibliotheque personnelle de webtoons et mangas. Le projet reste simple dans sa structure, mais integre des fonctionnalites concretes, une API externe, un suivi utilisateur, un espace admin et des protections de securite adaptees a un projet BTS SIO SLAM.
