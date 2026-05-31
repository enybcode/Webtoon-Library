<?php
// =============================================
// index.php — Page d'accueil
// =============================================

session_start();
$titre_page = "Accueil";
include 'includes/header.php';
// $base est maintenant disponible (défini dans header.php)
?>

<section class="hero">
    <h1>📚 Bienvenue sur Webtoon-Library</h1>
    <p>
        Suivez vos lectures, notez vos webtoons préférés et organisez
        votre bibliothèque personnelle. Simple, rapide et gratuit.
    </p>

    <div class="hero-boutons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= $base ?>/dashboard.php" class="btn btn-vert">Mon espace →</a>
        <?php else: ?>
            <a href="<?= $base ?>/inscription.php" class="btn btn-vert">Créer un compte</a>
            <a href="<?= $base ?>/connexion.php" class="btn btn-gris">Se connecter</a>
        <?php endif; ?>
    </div>
</section>

<h2 style="text-align:center; font-family:'Poppins',sans-serif; margin-bottom:1.5rem; color:#555;">
    Pourquoi utiliser Webtoon-Library ?
</h2>

<div class="grille-3">
    <div class="carte-feature">
        <div class="icone">📖</div>
        <h3>Suivi de lecture</h3>
        <p>Gardez une trace de vos lectures : à lire, en cours ou terminé. Ne perdez jamais votre avancement.</p>
    </div>
    <div class="carte-feature">
        <div class="icone">⭐</div>
        <h3>Notes personnelles</h3>
        <p>Notez vos webtoons sur 10 pour vous souvenir de vos coups de cœur et de vos déceptions.</p>
    </div>
    <div class="carte-feature">
        <div class="icone">🗂️</div>
        <h3>Bibliothèque privée</h3>
        <p>Votre liste est uniquement visible par vous. Chaque utilisateur a son espace personnel et sécurisé.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
