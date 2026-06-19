<?php
// =============================================
// dashboard.php - Tableau de bord utilisateur
// =============================================

session_start();
include 'includes/config.php';
include 'includes/traductions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['user_id'];

function chercherAnilistIdDashboard($titre)
{
    if (!function_exists('curl_init') || trim($titre) === '') {
        return null;
    }

    $query = '
        query ($search: String!) {
            Page(page: 1, perPage: 1) {
                media(search: $search, type: MANGA, isAdult: false) {
                    id
                    title { romaji english native }
                }
            }
        }
    ';

    $curl = curl_init('https://graphql.anilist.co');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
        'query' => $query,
        'variables' => ['search' => $titre]
    ]));
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_TIMEOUT, 8);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response === false || $httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    $resultat = $data['data']['Page']['media'][0] ?? null;

    return !empty($resultat['id']) ? (int)$resultat['id'] : null;
}

$reqStats = $pdo->prepare(
    "SELECT statut, COUNT(*) AS nb FROM webtoons WHERE id_utilisateur = ? GROUP BY statut"
);
$reqStats->execute([$userId]);

$total = 0;
$enCours = 0;
$termines = 0;
$aLire = 0;
$enPause = 0;
$abandonnes = 0;

foreach ($reqStats->fetchAll() as $ligne) {
    $total += $ligne['nb'];
    if ($ligne['statut'] === 'en_cours') $enCours = $ligne['nb'];
    if ($ligne['statut'] === 'termine') $termines = $ligne['nb'];
    if ($ligne['statut'] === 'a_lire') $aLire = $ligne['nb'];
    if ($ligne['statut'] === 'en_pause') $enPause = $ligne['nb'];
    if ($ligne['statut'] === 'abandonne') $abandonnes = $ligne['nb'];
}

$reqDerniers = $pdo->prepare(
    "SELECT * FROM webtoons WHERE id_utilisateur = ? ORDER BY date_ajout DESC LIMIT 4"
);
$reqDerniers->execute([$userId]);
$derniers = $reqDerniers->fetchAll();

foreach ($derniers as $index => $wt) {
    if (empty($wt['anilist_id']) && !empty($wt['titre'])) {
        $anilistIdTrouve = chercherAnilistIdDashboard($wt['titre']);

        if ($anilistIdTrouve) {
            $updateAnilist = $pdo->prepare(
                "UPDATE webtoons SET anilist_id = ? WHERE id = ? AND id_utilisateur = ?"
            );
            $updateAnilist->execute([$anilistIdTrouve, $wt['id'], $userId]);
            $derniers[$index]['anilist_id'] = $anilistIdTrouve;
        }
    }
}

$labelsStatut = [
    'a_lire' => t('to_read'),
    'en_cours' => t('reading'),
    'en_pause' => t('paused'),
    'abandonne' => t('dropped'),
    'termine' => t('finished')
];

$labelsIntention = [
    'continuer' => t('continue'),
    'hesite' => t('unsure'),
    'arreter' => t('stop')
];

$titre_page = t('dashboard');
include 'includes/header.php';
?>

<div class="entete-page">
    <h1 class="page-titre">
        <?= htmlspecialchars(langueCourante() === 'fr' ? 'Bonjour' : 'Hello') ?>,
        <?= htmlspecialchars($_SESSION['user_pseudo']) ?> !
    </h1>
    <a href="ajouter_webtoon.php" class="btn btn-vert">+ <?= htmlspecialchars(t('add_webtoon')) ?></a>
</div>

<div class="grille-stats">
    <div class="carte-stat">
        <div class="stat-nombre"><?= $total ?></div>
        <div class="stat-label"><?= htmlspecialchars(t('total_webtoons')) ?></div>
    </div>
    <div class="carte-stat">
        <div class="stat-nombre"><?= $enCours ?></div>
        <div class="stat-label"><?= htmlspecialchars(t('currently_reading')) ?></div>
    </div>
    <div class="carte-stat">
        <div class="stat-nombre"><?= $termines ?></div>
        <div class="stat-label"><?= htmlspecialchars(t('finished')) ?></div>
    </div>
    <div class="carte-stat">
        <div class="stat-nombre"><?= $aLire ?></div>
        <div class="stat-label"><?= htmlspecialchars(t('to_read')) ?></div>
    </div>
    <div class="carte-stat">
        <div class="stat-nombre"><?= $enPause ?></div>
        <div class="stat-label"><?= htmlspecialchars(t('paused')) ?></div>
    </div>
    <div class="carte-stat">
        <div class="stat-nombre"><?= $abandonnes ?></div>
        <div class="stat-label"><?= htmlspecialchars(t('dropped')) ?></div>
    </div>
</div>

<div class="entete-page">
    <h2 class="section-titre-gauche"><?= htmlspecialchars(t('recently_added')) ?></h2>
    <a href="webtoons.php" class="lien-action"><?= htmlspecialchars(t('view_all')) ?></a>
</div>

<?php if (empty($derniers)): ?>
    <div class="message-vide">
        <img src="<?= $base ?>/assets/img/icon-empty.svg" alt="" class="icone-vide-svg">
        <p>
            <?= htmlspecialchars(langueCourante() === 'fr' ? "Vous n'avez pas encore de webtoon." : 'You do not have any webtoon yet.') ?><br>
            <a href="ajouter_webtoon.php" class="btn btn-vert btn-espace-haut">
                <?= htmlspecialchars(t('first_webtoon')) ?>
            </a>
        </p>
    </div>
<?php else: ?>
    <div class="grille-webtoons">
        <?php foreach ($derniers as $wt): ?>
            <div class="carte-webtoon" data-statut="<?= htmlspecialchars($wt['statut']) ?>">
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
                    <div class="carte-webtoon-auteur"><?= htmlspecialchars($wt['auteur'] ?? t('unknown_author')) ?></div>

                    <span class="badge-statut badge-<?= htmlspecialchars($wt['statut']) ?>">
                        <?= htmlspecialchars($labelsStatut[$wt['statut']] ?? t('to_read')) ?>
                    </span>

                    <div class="carte-webtoon-meta">
                        <?= htmlspecialchars(t('chapters')) ?> <?= (int)($wt['chapitre_actuel'] ?? 0) ?>
                    </div>

                    <?php if (!is_null($wt['note'])): ?>
                        <div class="carte-webtoon-note"><?= (int)$wt['note'] ?>/10</div>
                    <?php endif; ?>

                    <div class="carte-webtoon-meta">
                        <?= htmlspecialchars($labelsIntention[$wt['intention'] ?? 'hesite'] ?? t('unsure')) ?>
                    </div>
                </div>

                <div class="card-actions">
                    <div class="card-actions-row">
                        <?php if (!empty($wt['anilist_id'])): ?>
                            <a href="detail_webtoon.php?id=<?= (int)$wt['anilist_id'] ?>" class="btn btn-gris btn-carte">
                                <?= htmlspecialchars(t('details')) ?>
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-gris btn-carte btn-desactive" disabled><?= htmlspecialchars(t('details_unavailable')) ?></button>
                        <?php endif; ?>

                        <a href="modifier_webtoon.php?id=<?= (int)$wt['id'] ?>" class="btn btn-vert btn-carte">
                            <?= htmlspecialchars(t('edit_progress')) ?>
                        </a>
                    </div>

                    <a href="#" class="btn-supprimer btn-carte card-action-full"
                       onclick="return confirmerSuppression('supprimer_webtoon.php?id=<?= (int)$wt['id'] ?>')">
                       <?= htmlspecialchars(t('delete')) ?>
                    </a>

                    <?php if (empty($wt['anilist_id'])): ?>
                        <small class="details-note">
                            <?= htmlspecialchars(t('anilist_no_match')) ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
