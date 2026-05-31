<?php
// =============================================
// dashboard.php — Tableau de bord utilisateur
// =============================================

session_start();
include 'includes/config.php';

// --- Protection de la page ---
// Si l'utilisateur n'est pas connecté, on le renvoie à la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// On récupère l'ID de l'utilisateur connecté depuis la session
$userId = $_SESSION['user_id'];

// ===== RÉCUPÉRATION DES STATISTIQUES EN UNE SEULE REQUÊTE =====
// On utilise GROUP BY + COUNT pour éviter 4 requêtes séparées
$reqStats = $pdo->prepare(
    "SELECT statut, COUNT(*) AS nb FROM webtoons WHERE id_utilisateur = ? GROUP BY statut"
);
$reqStats->execute([$userId]);

// On initialise les compteurs à 0
$total = 0; $enCours = 0; $termines = 0; $aLire = 0;

// On remplit les compteurs selon les résultats
foreach ($reqStats->fetchAll() as $ligne) {
    $total += $ligne['nb'];
    if ($ligne['statut'] === 'en_cours') $enCours  = $ligne['nb'];
    if ($ligne['statut'] === 'termine')  $termines = $ligne['nb'];
    if ($ligne['statut'] === 'a_lire')   $aLire    = $ligne['nb'];
}

// ===== RÉCUPÉRATION DES DERNIERS WEBTOONS =====
// On affiche les 4 derniers webtoons ajoutés
$reqDerniers = $pdo->prepare(
    "SELECT * FROM webtoons WHERE id_utilisateur = ? ORDER BY date_ajout DESC LIMIT 4"
);
$reqDerniers->execute([$userId]);
$derniers = $reqDerniers->fetchAll();

// Labels lisibles pour les statuts
$labelsStatut = [
    'a_lire'   => 'À lire',
    'en_cours' => 'En cours',
    'termine'  => 'Terminé'
];

$titre_page = "Mon espace";
include 'includes/header.php';
?>

<!-- ===== TITRE ET BOUTON AJOUTER ===== -->
<div class="entete-page">
    <h1 class="page-titre">
        👋 Bonjour, <?= htmlspecialchars($_SESSION['user_pseudo']) ?> !
    </h1>
    <a href="ajouter_webtoon.php" class="btn btn-vert">+ Ajouter un webtoon</a>
</div>

<!-- ===== STATISTIQUES ===== -->
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
        <div class="stat-label">Terminés</div>
    </div>
    <div class="carte-stat">
        <div class="stat-nombre"><?= $aLire ?></div>
        <div class="stat-label">À lire</div>
    </div>
</div>

<!-- ===== DERNIERS WEBTOONS AJOUTÉS ===== -->
<div class="entete-page">
    <h2 style="font-family:'Poppins',sans-serif; color:#555;">Ajoutés récemment</h2>
    <a href="webtoons.php" style="font-weight:700; color:var(--vert-fonce);">Voir tout →</a>
</div>

<?php if (empty($derniers)): ?>
    <!-- Cas où l'utilisateur n'a pas encore de webtoon -->
    <div class="message-vide">
        <div class="icone-vide">📭</div>
        <p>Vous n'avez pas encore de webtoon.<br>
           <a href="ajouter_webtoon.php" class="btn btn-vert" style="margin-top:1rem; display:inline-block;">
               Ajouter mon premier webtoon
           </a>
        </p>
    </div>
<?php else: ?>
    <div class="grille-webtoons">
        <?php foreach ($derniers as $wt): ?>
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
                    <div class="carte-webtoon-auteur"><?= htmlspecialchars($wt['auteur'] ?? 'Auteur inconnu') ?></div>

                    <!-- Badge statut -->
                    <span class="badge-statut badge-<?= $wt['statut'] ?>">
                        <?= $labelsStatut[$wt['statut']] ?>
                    </span>

                    <!-- Note si elle existe -->
                    <?php if (!is_null($wt['note'])): ?>
                        <div class="carte-webtoon-note">⭐ <?= $wt['note'] ?>/10</div>
                    <?php endif; ?>
                </div>

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
