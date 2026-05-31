<?php
// =============================================
// header.php — En-tête commun à toutes les pages
// =============================================

// On démarre la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre_page) ? htmlspecialchars($titre_page) . ' — WebtoonLib' : 'WebtoonLib' ?></title>
    <link rel="stylesheet" href="/webtoon-app/assets/css/style.css">
    <!-- Icônes depuis Google Fonts (Material Icons) -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- ===== BARRE DE NAVIGATION ===== -->
<nav class="navbar">
    <a href="/webtoon-app/index.php" class="navbar-logo">
        📚 WebtoonLib
    </a>

    <ul class="navbar-liens">
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Liens pour les utilisateurs connectés -->
            <li><a href="/webtoon-app/dashboard.php">Mon espace</a></li>
            <li><a href="/webtoon-app/webtoons.php">Ma liste</a></li>
            <li><a href="/webtoon-app/ajouter_webtoon.php" class="btn-nav">+ Ajouter</a></li>
            <li><a href="/webtoon-app/logout.php" class="btn-nav btn-rouge">Déconnexion</a></li>
        <?php else: ?>
            <!-- Liens pour les visiteurs non connectés -->
            <li><a href="/webtoon-app/connexion.php">Connexion</a></li>
            <li><a href="/webtoon-app/inscription.php" class="btn-nav">S'inscrire</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Le contenu de chaque page commence ici -->
<main class="contenu-principal">
