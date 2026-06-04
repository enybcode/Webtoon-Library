<?php
// =============================================
// dashboard.php - Tableau de bord utilisateur
// =============================================

session_start();
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['user_id'];

$reqStats = $pdo->prepare(
    "SELECT statut, COUNT(*) AS nb FROM webtoons WHERE id_utilisateur = ? GROUP BY statut"
);
$reqStats->execute([$userId]);

$total = 0; $enCours = 0; $termines = 0; $aLire = 0;

foreach ($reqStats->fetchAll() as $ligne) {
    $total += $ligne['nb'];
    if ($ligne['statut'] === 'en_cours') $enCours  = $ligne['nb'];
    if ($ligne['statut'] === 'termine')  $termines = $ligne['nb'];
    if ($ligne['statut'] === 'a_lire')   $aLire    = $ligne['nb'];
}

$reqDerniers = $pdo->prepare(
    "SELECT * FROM webtoons WHERE id_utilisateur = ? ORDER BY date_ajout DESC LIMIT 4"
);
$reqDerniers->execute([$userId]);
$derniers = $reqDerniers->fetchAll();

$labelsStatut = [
    'a_lire'   => 'A lire',
    'en_cours' => 'En cours',
    'termine'  => 'Termine'
];

$titre_page = "Mon espace";
include 'includes/header.php';
?>

<div class="entete-page">
    <h1 class="page-titre">Bonjour, <?= htmlspecialchars($_SESSION['user_pseudo']) ?> !</h1>
    <a href="ajouter_webtoon.php" class="btn btn-vert">+ Ajouter un webtoon</a>
</div>

<div class="grille-stats">
    <div class="carte-stat">
        <div class="stat-nombre"><?= $total ?></div>
        <div class="stat-label">Webtoons au total</div>
    </div>
    <div class="carte-stat">
        <div class="stat-nombre"><?= $enCours ?></div>
        <div class="stat-label">En cours de lecture</div>
    </div>
    <div class="carte-stat">
        <div class="stat-nombre"><?= $termines ?></div>
        <div class="stat-label">Termines</div>
    </div>
    <div class="carte-stat">
        <div class="stat-nombre"><?= $aLire ?></div>
        <div class="stat-label">A lire</div>
    </div>
</div>

<div class="entete-page">
    <h2 class="section-titre-gauche">Ajoutes recemment</h2>
    <a href="webtoons.php" class="lien-action">Voir tout</a>
</div>

<?php if (empty($derniers)): ?>
    <div class="message-vide">
        <img src="<?= $base ?>/assets/img/icon-empty.svg" alt="" class="icone-vide-svg">
        <p>Vous n'avez pas encore de webtoon.<br>
           <a href="ajouter_webtoon.php" class="btn btn-vert btn-espace-haut">
               Ajouter mon premier webtoon
           </a>
        </p>
    </div>
<?php else: ?>
    <div class="grille-webtoons">
        <?php foreach ($derniers as $wt): ?>
            <div class="carte-webtoon" data-statut="<?= $wt['statut'] ?>">
                <?php if (!empty($wt['image_url'])): ?>
                    <img class="carte-webtoon-image"
                         src="<?= htmlspecialchars($wt['image_url']) ?>"
                         alt="<?= htmlspecialchars($wt['titre']) ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="carte-webtoon-image-placeholder" style="display:none;">
                        <img src="<?= $base ?>/assets/img/icon-book.svg" alt="" class="placeholder-icon">
                    </div>
                <?php else: ?>
                    <div class="carte-webtoon-image-placeholder">
                        <img src="<?= $base ?>/assets/img/icon-book.svg" alt="" class="placeholder-icon">
                    </div>
                <?php endif; ?>

                <div class="carte-webtoon-corps">
                    <div class="carte-webtoon-titre"><?= htmlspecialchars($wt['titre']) ?></div>
                    <div class="carte-webtoon-auteur"><?= htmlspecialchars($wt['auteur'] ?? 'Auteur inconnu') ?></div>

                    <span class="badge-statut badge-<?= $wt['statut'] ?>">
                        <?= $labelsStatut[$wt['statut']] ?>
                    </span>

                    <?php if (!is_null($wt['note'])): ?>
                        <div class="carte-webtoon-note"><?= $wt['note'] ?>/10</div>
                    <?php endif; ?>
                </div>

                <div class="carte-webtoon-actions">
                    <a href="modifier_webtoon.php?id=<?= $wt['id'] ?>" class="btn-modifier">
                        <img src="<?= $base ?>/assets/img/icon-edit.svg" alt="" class="action-icon"> Modifier
                    </a>
                    <a href="#" class="btn-supprimer"
                       onclick="return confirmerSuppression('supprimer_webtoon.php?id=<?= $wt['id'] ?>')">
                       <img src="<?= $base ?>/assets/img/icon-trash.svg" alt="" class="action-icon"> Supprimer
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
