<?php
// =============================================
// config.php — Connexion à la base de données
// =============================================

// Informations de connexion à MySQL
define('DB_HOST', 'localhost');
define('DB_NOM',  'webtoon_app');
define('DB_USER', 'root');
define('DB_PASS', '');  // Mot de passe vide par défaut sur XAMPP/WAMP

// On tente de se connecter avec PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NOM . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );

    // On configure PDO pour afficher les erreurs proprement
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Les résultats seront des tableaux associatifs (ex: $ligne['titre'])
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la connexion échoue, on affiche un message d'erreur et on arrête
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
