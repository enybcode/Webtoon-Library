# Webtoon Library sur Windows

Cette installation sert au développement sur votre PC. Elle ne déploie rien sur le site public et ne remplace pas un serveur de production.

1. Installer PHP 8.4 pour Windows depuis https://www.php.net/downloads.php et activer `pdo_sqlite`, `sqlite3`, `curl`, `mbstring` et `openssl` dans `php.ini`. Conserver la vérification TLS et un magasin de certificats valide pour les API HTTPS.
2. Mettre `php.exe` dans le PATH, ou placer la distribution PHP dans `runtime/php/` à la racine du dépôt.
3. Lancer `Demarrer.cmd`, puis ouvrir http://127.0.0.1:8088. Le serveur écoute uniquement sur cette machine.
4. Lancer `Arreter.cmd` pour arrêter ce serveur.

Alternative PowerShell avec un PHP déjà installé :

```powershell
.\Demarrer.ps1 -PhpPath 'C:\chemin\php.exe' -SansNavigateur
```

La base SQLite est créée par l'application dans `data/webtoon_library.sqlite`. Elle reste présente après l'arrêt du serveur. Pour la sauvegarder, arrêter le site puis copier `data/` dans un emplacement de sauvegarde. Créer votre compte depuis l'inscription ; le compte de démonstration documenté dans le README reste un compte de test.

Les logs sont dans `logs/`. `runtime/`, `data/`, `logs/` et les fichiers `.env` sont exclus de Git. Ne pas versionner les sessions, données personnelles ou secrets. Le routeur local refuse les répertoires techniques et les fichiers cachés ; il n'est utilisé que par le lanceur local. Les scripts SQL MySQL existants restent disponibles dans `database/`.

Les recherches AniList nécessitent Internet ; DeepL nécessite une configuration API valide. Le lancement local ne configure pas ces comptes.
