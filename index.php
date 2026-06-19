<?php
// =============================================
// index.php - Page d'accueil
// =============================================

session_start();
include 'includes/lang.php';
$titre_page = t('home');
include 'includes/header.php';
?>

<section class="hero">
    <h1><?= htmlspecialchars(t('welcome')) ?></h1>
    <p>
        <?= htmlspecialchars(t('welcome_text')) ?>
    </p>

    <div class="hero-boutons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= $base ?>/dashboard.php" class="btn btn-vert"><?= htmlspecialchars(t('dashboard')) ?></a>
        <?php else: ?>
            <a href="<?= $base ?>/inscription.php" class="btn btn-vert"><?= htmlspecialchars(t('create_account')) ?></a>
            <a href="<?= $base ?>/connexion.php" class="btn btn-gris"><?= htmlspecialchars(t('login')) ?></a>
        <?php endif; ?>
    </div>
</section>

<h2 class="section-titre"><?= langueCourante() === 'fr' ? 'Pourquoi utiliser Webtoon-Library ?' : 'Why use Webtoon-Library?' ?></h2>

<div class="grille-3">
    <div class="carte-feature">
        <img src="<?= $base ?>/assets/img/icon-book.svg" alt="" class="icone-svg">
        <h3><?= langueCourante() === 'fr' ? 'Suivi de lecture' : 'Reading progress' ?></h3>
        <p><?= langueCourante() === 'fr' ? 'Gardez une trace de vos lectures : à lire, en cours ou terminé.' : 'Keep track of what you want to read, what you are reading and what you finished.' ?></p>
    </div>
    <div class="carte-feature">
        <img src="<?= $base ?>/assets/img/icon-star.svg" alt="" class="icone-svg">
        <h3><?= langueCourante() === 'fr' ? 'Notes personnelles' : 'Personal ratings' ?></h3>
        <p><?= langueCourante() === 'fr' ? 'Notez vos webtoons sur 10 et ajoutez vos commentaires.' : 'Rate your webtoons out of 10 and keep your own comments.' ?></p>
    </div>
    <div class="carte-feature">
        <img src="<?= $base ?>/assets/img/icon-library.svg" alt="" class="icone-svg">
        <h3><?= langueCourante() === 'fr' ? 'Bibliothèque privée' : 'Private library' ?></h3>
        <p><?= langueCourante() === 'fr' ? 'Votre liste est uniquement visible par vous.' : 'Your list belongs to your personal account.' ?></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
