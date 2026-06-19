<?php
// =============================================
// header.php - En-tete commun a toutes les pages
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/lang.php';

// Calcule le chemin de base du projet pour charger les liens et les assets.
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre_page) ? htmlspecialchars($titre_page) . ' - Webtoon-Library' : 'Webtoon-Library' ?></title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css?v=7">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<nav class="navbar">
    <a href="<?= $base ?>/index.php" class="navbar-logo">
        <img src="<?= $base ?>/assets/img/logo-webtoon-library.svg" alt="" class="logo-icone">
        <span>Webtoon-Library</span>
    </a>

    <ul class="navbar-liens">
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?= $base ?>/dashboard.php"><?= htmlspecialchars(t('dashboard')) ?></a></li>
            <li><a href="<?= $base ?>/tendance.php"><?= htmlspecialchars(t('trends')) ?></a></li>
            <li><a href="<?= $base ?>/rechercher.php"><?= htmlspecialchars(t('search')) ?></a></li>
            <?php if (!empty($_SESSION['is_admin'])): ?>
                <li><a href="<?= $base ?>/admin_db.php">Base</a></li>
            <?php endif; ?>
            <li><a href="<?= $base ?>/ajouter_webtoon.php" class="btn-nav">+ <?= htmlspecialchars(t('add')) ?></a></li>
            <li>
                <a href="<?= $base ?>/parametres.php" class="btn-parametres" title="<?= htmlspecialchars(t('settings')) ?>">
                    <img src="<?= $base ?>/assets/img/icon-settings.svg" alt="<?= htmlspecialchars(t('settings')) ?>">
                </a>
            </li>
        <?php else: ?>
            <li><a href="<?= $base ?>/connexion.php"><?= htmlspecialchars(t('login')) ?></a></li>
            <li><a href="<?= $base ?>/inscription.php" class="btn-nav"><?= htmlspecialchars(t('register')) ?></a></li>
        <?php endif; ?>
    </ul>
</nav>

<main class="contenu-principal">
