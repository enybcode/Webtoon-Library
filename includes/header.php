<?php
// =============================================
// header.php — En-tête commun à toutes les pages
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Calcul du chemin de base (URL) ──────────────────────────────────────────
// SCRIPT_NAME = chemin URL de la page en cours, ex: /webtoon_library/dashboard.php
// dirname()   = retire le nom du fichier      → /webtoon_library
// str_replace = normalise les \ Windows en /
// rtrim       = enlève le slash final
//
// Exemples de résultat :
//   http://localhost/webtoon_library/  → $base = "/webtoon_library"
//   http://localhost/Webtoon-Library/  → $base = "/Webtoon-Library"
//   http://monsite.com/ (vhost racine) → $base = ""
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre_page) ? htmlspecialchars($titre_page) . ' — Webtoon-Library' : 'Webtoon-Library' ?></title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<nav class="navbar">
    <a href="<?= $base ?>/index.php" class="navbar-logo">
        📚 Webtoon-Library
    </a>

    <ul class="navbar-liens">
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?= $base ?>/dashboard.php">Mon espace</a></li>
            <li><a href="<?= $base ?>/webtoons.php">Ma liste</a></li>
            <li><a href="<?= $base ?>/ajouter_webtoon.php" class="btn-nav">+ Ajouter</a></li>
            <li><a href="<?= $base ?>/logout.php" class="btn-nav btn-rouge">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="<?= $base ?>/connexion.php">Connexion</a></li>
            <li><a href="<?= $base ?>/inscription.php" class="btn-nav">S'inscrire</a></li>
        <?php endif; ?>
    </ul>
</nav>

<main class="contenu-principal">
