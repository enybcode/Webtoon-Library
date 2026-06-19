<?php
// =============================================
// config.php — Connexion à la base de données
// =============================================

// Par defaut le projet utilise MySQL, comme sur XAMPP.
// Sur le serveur, on peut mettre WEBTOON_DB_DRIVER=sqlite pour eviter d'installer MySQL.
$driver = getenv('WEBTOON_DB_DRIVER') ?: 'mysql';
$nomBase = 'webtoon_library';

if (!defined('DB_HOST')) define('DB_HOST', getenv('WEBTOON_DB_HOST') ?: 'localhost');
if (!defined('DB_NOM'))  define('DB_NOM',  getenv('WEBTOON_DB_NAME') ?: $nomBase);
if (!defined('DB_USER')) define('DB_USER', getenv('WEBTOON_DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('WEBTOON_DB_PASS') ?: '');

function initialiserBaseSqlite($pdo)
{
    $pdo->exec("PRAGMA foreign_keys = ON");

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS utilisateurs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            pseudo TEXT NOT NULL UNIQUE,
            mot_de_passe TEXT NOT NULL,
            langue TEXT NOT NULL DEFAULT 'en',
            is_admin INTEGER NOT NULL DEFAULT 0,
            date_creation TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $colonnesUtilisateurs = [];
    foreach ($pdo->query("PRAGMA table_info(utilisateurs)") as $colonne) {
        $colonnesUtilisateurs[] = $colonne['name'];
    }

    if (!in_array('is_admin', $colonnesUtilisateurs, true)) {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN is_admin INTEGER NOT NULL DEFAULT 0");
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS webtoons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_utilisateur INTEGER NOT NULL,
            anilist_id INTEGER NULL,
            titre TEXT NOT NULL,
            auteur TEXT,
            genre TEXT,
            description TEXT,
            statut TEXT DEFAULT 'a_lire',
            chapitre_actuel INTEGER DEFAULT 0,
            note INTEGER NULL,
            commentaire TEXT,
            intention TEXT DEFAULT 'hesite',
            image_url TEXT,
            date_ajout TEXT DEFAULT CURRENT_TIMESTAMP,
            date_modification TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS traductions_cache (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            texte_hash TEXT NOT NULL,
            texte_original TEXT NOT NULL,
            langue_cible TEXT NOT NULL,
            texte_traduit TEXT NOT NULL,
            date_creation TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (texte_hash, langue_cible)
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS categories_admin (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom_anilist TEXT NOT NULL UNIQUE,
            label_fr TEXT NOT NULL,
            label_en TEXT NOT NULL,
            actif INTEGER NOT NULL DEFAULT 1,
            adulte INTEGER NOT NULL DEFAULT 1,
            ordre_affichage INTEGER NOT NULL DEFAULT 0
        )"
    );

    $reqCategories = $pdo->query("SELECT COUNT(*) FROM categories_admin");
    if ((int)$reqCategories->fetchColumn() === 0) {
        $categoriesDefaut = [
            ['Action', 'Action', 'Action', 1, 10],
            ['Fantasy', 'Fantasy', 'Fantasy', 1, 20],
            ['Romance', 'Romance', 'Romance', 1, 30],
            ['Drama', 'Drame', 'Drama', 1, 40],
            ['Comedy', 'Comedie', 'Comedy', 1, 50],
            ['Horror', 'Horreur', 'Horror', 1, 60],
            ['Adventure', 'Aventure', 'Adventure', 1, 70],
            ['Mystery', 'Mystere', 'Mystery', 1, 80],
            ['Sports', 'Sport', 'Sports', 0, 90],
            ['Sci-Fi', 'Science-fiction', 'Sci-Fi', 1, 100],
            ['Slice of Life', 'Tranche de vie', 'Slice of Life', 1, 110],
            ['Supernatural', 'Surnaturel', 'Supernatural', 1, 120],
            ['Thriller', 'Thriller', 'Thriller', 1, 130],
            ['Psychological', 'Psychologique', 'Psychological', 1, 140]
        ];
        $insertCategorie = $pdo->prepare(
            "INSERT INTO categories_admin (nom_anilist, label_fr, label_en, adulte, ordre_affichage)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($categoriesDefaut as $categorie) {
            $insertCategorie->execute($categorie);
        }
    }

    $reqCount = $pdo->query("SELECT COUNT(*) AS total FROM utilisateurs");
    $count = (int)$reqCount->fetchColumn();

    if ($count === 0) {
        $insertUser = $pdo->prepare(
            "INSERT INTO utilisateurs (email, pseudo, mot_de_passe, langue, is_admin)
             VALUES (?, ?, ?, 'en', 0)"
        );
        $insertUser->execute([
            'test@example.com',
            'TestUser',
            password_hash('password', PASSWORD_DEFAULT)
        ]);

        $insertWebtoon = $pdo->prepare(
            "INSERT INTO webtoons
             (id_utilisateur, anilist_id, titre, auteur, genre, description, statut, chapitre_actuel, note, commentaire, intention, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insertWebtoon->execute([
            1,
            105398,
            'Solo Leveling',
            'Chugong',
            'Action, Fantasy',
            'In a world where awakened hunters fight monsters, Sung Jin-Woo receives a strange second chance to grow stronger.',
            'termine',
            179,
            9,
            'Demo entry.',
            'continuer',
            'https://s4.anilist.co/file/anilistcdn/media/manga/cover/large/bx105398-b673Vt5ZSuzX.jpg'
        ]);
    }
}

try {
    if ($driver === 'sqlite') {
        $sqlitePath = getenv('WEBTOON_SQLITE_PATH') ?: __DIR__ . '/../database/webtoon_library.sqlite';
        $sqliteDir = dirname($sqlitePath);

        if (!is_dir($sqliteDir)) {
            mkdir($sqliteDir, 0775, true);
        }

        $pdo = new PDO("sqlite:" . $sqlitePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initialiserBaseSqlite($pdo);
    } else {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NOM . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die(
        "<strong>Erreur de connexion a la base " . htmlspecialchars(DB_NOM) . "</strong><br>" .
        htmlspecialchars($e->getMessage())
    );
}
?>
