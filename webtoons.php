<?php
// =============================================
// webtoons.php — Liste de tous mes webtoons
// =============================================

session_start();
include 'includes/config.php';

// Protection de la page
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['user_id'];

// ===== RÉCUPÉRATION DE TOUS LES WEBTOONS =====
// On récupère uniquement les webtoons de l'utilisateur connecté
$requete = $pdo->prepare(
    "SELECT * FROM webtoons WHERE id_utilisateur = ? ORDER BY date_ajout DESC"
);
$requete->execute([$userId]);
$webtoons = $requete->fetchAll();

// Labels lisibles pour les statuts
$labelsStatut = [
    'a_lire'   => 'À lire',
    'en_cours' => 'En cours',
    'termine'  => 'Terminé'
];

$titre_page = "Ma liste";
include 'includes/header.php';
?>

<!-- ===== EN-TÊTE ===== -->
<div class="entete-page">
    <h1 class="page-titre">📋 Ma liste de webtoons</h1>
    <a href="ajouter_webtoon.php" class="btn btn-vert">+ Ajouter</a>
</div>

<?php if (empty($webtoons)): ?>
    <!-- Cas vide -->
    <div class="message-vide">
        <div class="icone-vide">📭</div>
        <p>Votre liste est vide pour l'instant.<br>
           <a href="ajouter_webtoon.php" class="btn btn-vert" style="margin-top:1rem; display:inline-block;">
               Ajouter mon premier webtoon
           </a>
        </p>
    </div>
<?php else: ?>

    <!-- ===== BOUTONS DE FILTRE ===== -->
    <div class="barre-filtres">
        <button class="filtre-btn" data-filtre="tous" onclick="filtrerWebtoons('tous')">
            Tous (<?= count($webtoons) ?>)
        </button>
        <button class="filtre-btn" data-filtre="en_cours" onclick="filtrerWebtoons('en_cours')">
            🟡 En cours
        </button>
        <button class="filtre-btn" data-filtre="a_lire" onclick="filtrerWebtoons('a_lire')">
            🔵 À lire
        </button>
        <button class="filtre-btn" data-filtre="termine" onclick="filtrerWebtoons('termine')">
            🟢 Terminés
        </button>
    </div>

    <!-- ===== GRILLE DES WEBTOONS ===== -->
    <div class="grille-webtoons">
        <?php foreach ($webtoons as $wt): ?>
            <!-- data-statut est utilisé par le filtre JavaScript -->
            <div class="carte-webtoon" data-statut="<?= $wt['statut'] ?>">

                <!-- Image ou placeholder -->
                <?php if (!empty($wt['image_url'])): ?>
                    <img class="carte-webtoon-image"
                         src="<?= htmlspecialchars($wt['image_url']) ?>"
                         alt="<?= htmlspecialchars($wt['titre']) ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="carte-webtoon-image-placeholder" style="display:none;">📚</div>
                <?php else: ?>
                    <div class="carte-webtoon-image-placeholder">📚</div>
                <?php endif; ?>

                <div class="carte-webtoon-corps">
                    <div class="carte-webtoon-titre"><?= htmlspecialchars($wt['titre']) ?></div>
                    <div class="carte-webtoon-auteur">
                        <?= htmlspecialchars($wt['auteur'] ?? 'Inconnu') ?>
                        <?php if ($wt['genre']): ?>
                            · <em><?= htmlspecialchars($wt['genre']) ?></em>
                        <?php endif; ?>
                    </div>

                    <!-- Badge statut -->
                    <span class="badge-statut badge-<?= $wt['statut'] ?>">
                        <?= $labelsStatut[$wt['statut']] ?>
                    </span>

                    <!-- Chapitre actuel -->
                    <?php if ($wt['chapitre_actuel'] > 0): ?>
                        <div style="font-size:0.8rem; color:#777; margin-top:0.3rem;">
                            📖 Chapitre <?= $wt['chapitre_actuel'] ?>
                        </div>
                    <?php endif; ?>

                    <!-- Note -->
                    <?php if (!is_null($wt['note'])): ?>
                        <div class="carte-webtoon-note">⭐ <?= $wt['note'] ?>/10</div>
                    <?php endif; ?>
                </div>

                <!-- Boutons d'action -->
                <div class="carte-webtoon-actions">
                    <a href="modifier_webtoon.php?id=<?= $wt['id'] ?>" class="btn-modifier">✏️ Modifier</a>
                    <a href="#" class="btn-supprimer"
                       onclick="return confirmerSuppression('supprimer_webtoon.php?id=<?= $wt['id'] ?>')">
                       🗑️ Supprimer
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
