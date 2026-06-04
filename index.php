<?php
// =============================================
// index.php - Page d'accueil
// =============================================

session_start();
$titre_page = "Accueil";
include 'includes/header.php';
?>

<section class="hero">
    <h1>Bienvenue sur Webtoon-Library</h1>
    <p>
        Suivez vos lectures, notez vos webtoons preferes et organisez
        votre bibliotheque personnelle. Simple, rapide et gratuit.
    </p>

    <div class="hero-boutons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= $base ?>/dashboard.php" class="btn btn-vert">Mon espace</a>
        <?php else: ?>
            <a href="<?= $base ?>/inscription.php" class="btn btn-vert">Creer un compte</a>
            <a href="<?= $base ?>/connexion.php" class="btn btn-gris">Se connecter</a>
        <?php endif; ?>
    </div>
</section>

<h2 class="section-titre">Pourquoi utiliser Webtoon-Library ?</h2>

<div class="grille-3">
    <div class="carte-feature">
        <img src="<?= $base ?>/assets/img/icon-book.svg" alt="" class="icone-svg">
        <h3>Suivi de lecture</h3>
        <p>Gardez une trace de vos lectures : a lire, en cours ou termine. Ne perdez jamais votre avancement.</p>
    </div>
    <div class="carte-feature">
        <img src="<?= $base ?>/assets/img/icon-star.svg" alt="" class="icone-svg">
        <h3>Notes personnelles</h3>
        <p>Notez vos webtoons sur 10 pour vous souvenir de vos coups de coeur et de vos deceptions.</p>
    </div>
    <div class="carte-feature">
        <img src="<?= $base ?>/assets/img/icon-library.svg" alt="" class="icone-svg">
        <h3>Bibliotheque privee</h3>
        <p>Votre liste est uniquement visible par vous. Chaque utilisateur a son espace personnel et securise.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
