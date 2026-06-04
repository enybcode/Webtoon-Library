<?php
// =============================================
// webtoons.php - Liste de tous mes webtoons
// =============================================

session_start();
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['user_id'];

$requete = $pdo->prepare(
    "SELECT * FROM webtoons WHERE id_utilisateur = ? ORDER BY date_ajout DESC"
);
$requete->execute([$userId]);
$webtoons = $requete->fetchAll();

$labelsStatut = [
    'a_lire'   => 'A lire',
    'en_cours' => 'En cours',
    'termine'  => 'Termine'
];

$titre_page = "Ma liste";
include 'includes/header.php';

if (isset($_GET['ajout']))    echo '<div class="alerte alerte-succes">Webtoon ajoute avec succes !</div>';
if (isset($_GET['modif']))    echo '<div class="alerte alerte-succes">Webtoon modifie avec succes !</div>';
if (isset($_GET['supprime'])) echo '<div class="alerte alerte-succes">Webtoon supprime.</div>';
?>

<div class="entete-page">
    <h1 class="page-titre">Ma liste de webtoons</h1>
    <a href="ajouter_webtoon.php" class="btn btn-vert">+ Ajouter</a>
</div>

<?php if (empty($webtoons)): ?>
    <div class="message-vide">
        <img src="<?= $base ?>/assets/img/icon-empty.svg" alt="" class="icone-vide-svg">
        <p>Votre liste est vide pour l'instant.<br>
           <a href="ajouter_webtoon.php" class="btn btn-vert btn-espace-haut">
               Ajouter mon premier webtoon
           </a>
        </p>
    </div>
<?php else: ?>

    <div class="barre-filtres">
        <button class="filtre-btn" data-filtre="tous" onclick="filtrerWebtoons('tous')">
            Tous (<?= count($webtoons) ?>)
        </button>
        <button class="filtre-btn" data-filtre="en_cours" onclick="filtrerWebtoons('en_cours')">
            En cours
        </button>
        <button class="filtre-btn" data-filtre="a_lire" onclick="filtrerWebtoons('a_lire')">
            A lire
        </button>
        <button class="filtre-btn" data-filtre="termine" onclick="filtrerWebtoons('termine')">
            Termines
        </button>
    </div>

    <div class="grille-webtoons">
        <?php foreach ($webtoons as $wt): ?>
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
                    <div class="carte-webtoon-auteur">
                        <?= htmlspecialchars($wt['auteur'] ?? 'Inconnu') ?>
                        <?php if ($wt['genre']): ?>
                            - <em><?= htmlspecialchars($wt['genre']) ?></em>
                        <?php endif; ?>
                    </div>

                    <span class="badge-statut badge-<?= $wt['statut'] ?>">
                        <?= $labelsStatut[$wt['statut']] ?>
                    </span>

                    <?php if ($wt['chapitre_actuel'] > 0): ?>
                        <div class="carte-webtoon-meta">
                            Chapitre <?= $wt['chapitre_actuel'] ?>
                        </div>
                    <?php endif; ?>

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
