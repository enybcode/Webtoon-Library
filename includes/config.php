<?php
// =============================================
// config.php — Connexion à la base de données
// =============================================

// Détecte automatiquement le nom de la BDD depuis le nom du dossier projet.
// Ex: htdocs/webtoon_library  → BDD : webtoon_library
// Ex: htdocs/Webtoon-Library  → BDD : Webtoon-Library
$nomBase = 'webtoon_library';

// On utilise defined() pour éviter une erreur si config.php est inclus deux fois
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NOM'))  define('DB_NOM',  $nomBase);
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', ''); // Vide par défaut sur XAMPP/WAMP

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NOM . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die(
        "<strong>Erreur de connexion à la base « " . DB_NOM . " »</strong><br>" .
        $e->getMessage() .
        "<br><br>💡 Vérifie que la base <strong>" . DB_NOM . "</strong> existe dans phpMyAdmin " .
        "et que le nom correspond exactement au nom de ton dossier dans htdocs/."
    );
}
?>
